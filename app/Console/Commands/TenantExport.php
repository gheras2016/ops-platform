<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Support\TenantExporter;
use Illuminate\Console\Command;

class TenantExport extends Command
{
    protected $signature = 'tenant:export {company : Company id or code}';

    protected $description = 'Export all data for a single company (tenant) as a ZIP archive.';

    public function handle(TenantExporter $exporter): int
    {
        $arg = $this->argument('company');

        $company = Company::where('id', $arg)->orWhere('code', $arg)->first();

        if (! $company) {
            $this->error("لم يتم العثور على شركة بالمعرّف أو الرمز [{$arg}].");

            return self::FAILURE;
        }

        $path = $exporter->export($company);

        $this->info("تم تصدير بيانات «{$company->name}» إلى:");
        $this->line($path);

        return self::SUCCESS;
    }
}
