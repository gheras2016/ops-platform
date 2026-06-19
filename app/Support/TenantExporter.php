<?php

namespace App\Support;

use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Exports every row belonging to a single company (tenant) into a ZIP of
 * JSON files — one per table — plus a manifest. Used both by the
 * `tenant:export` console command and the super-admin download button so a
 * company's full data can be handed over on subscription end.
 *
 * Queries use the raw query builder (no Eloquent global scopes) so the export
 * is independent of the acting user and runs cleanly from the CLI.
 */
class TenantExporter
{
    /** Tables owned directly via a company_id column. */
    protected array $owned = [
        'users', 'departments', 'locations', 'asset_categories', 'assets',
        'categories', 'items', 'spare_categories', 'spare_parts', 'tickets',
        'part_requests', 'purchase_requests', 'purchase_orders',
        'stock_transactions', 'audit_logs',
    ];

    /** Build the ZIP archive and return its absolute path. */
    public function export(Company $company): string
    {
        $payload = $this->collect($company);

        $dir = storage_path('app/exports');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $slug = Str::slug($company->code ?: ('company-' . $company->id)) ?: ('company-' . $company->id);
        $path = $dir . '/tenant_' . $slug . '_' . now()->format('Ymd_His') . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('تعذّر إنشاء ملف التصدير.');
        }

        foreach ($payload as $name => $rows) {
            $zip->addFromString($name . '.json', json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        $zip->close();

        return $path;
    }

    /**
     * Gather all of the company's data, keyed by file name.
     *
     * @return array<string, mixed>
     */
    public function collect(Company $company): array
    {
        $id = $company->id;
        $data = [];

        $data['_manifest'] = [
            'company' => ['id' => $company->id, 'name' => $company->name, 'code' => $company->code],
            'exported_at' => now()->toIso8601String(),
            'platform' => config('app.name'),
            'format' => 'one JSON file per database table; rows are raw records.',
        ];

        $data['company'] = DB::table('companies')->where('id', $id)->get();

        foreach ($this->owned as $table) {
            $rows = DB::table($table)->where('company_id', $id)->get();

            // Never hand over credential hashes.
            if ($table === 'users') {
                $rows = $rows->map(fn ($r) => collect((array) $r)->except(['password', 'remember_token']));
            }

            $data[$table] = $rows;
        }

        // Child tables — isolated transitively through their parent record.
        $ticketIds = DB::table('tickets')->where('company_id', $id)->pluck('id');
        foreach (['ticket_events', 'ticket_pause_logs', 'ticket_comments', 'ticket_attachments', 'ticket_spare_parts'] as $t) {
            $data[$t] = DB::table($t)->whereIn('ticket_id', $ticketIds)->get();
        }

        $partRequestIds = DB::table('part_requests')->where('company_id', $id)->pluck('id');
        $data['part_request_items'] = DB::table('part_request_items')->whereIn('part_request_id', $partRequestIds)->get();

        $prIds = DB::table('purchase_requests')->where('company_id', $id)->pluck('id');
        $data['purchase_approvals'] = DB::table('purchase_approvals')->whereIn('purchase_request_id', $prIds)->get();
        $data['purchase_request_items'] = DB::table('purchase_request_items')->whereIn('purchase_request_id', $prIds)->get();

        $poIds = DB::table('purchase_orders')->where('company_id', $id)->pluck('id');
        $data['purchase_order_items'] = DB::table('purchase_order_items')->whereIn('purchase_order_id', $poIds)->get();

        return $data;
    }
}
