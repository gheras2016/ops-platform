<?php

namespace App\Http\Controllers;

use App\Exports\TicketsExport;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketPauseLog;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view-reports');

        $data = $this->buildData($request);

        return view('reports.index', $data);
    }

    /**
     * Export the filtered report in the requested format.
     * Department heads are automatically restricted to their own department(s).
     */
    public function export(Request $request, string $format = 'csv')
    {
        $this->authorize('view-reports');

        $data = $this->buildData($request);
        $tickets = $data['tickets'];
        $stamp = now()->format('Ymd_His');

        return match ($format) {
            'xlsx' => Excel::download(new TicketsExport($tickets), "tickets_report_{$stamp}.xlsx"),
            'pdf' => $this->pdf($data, $stamp),
            default => $this->csv($tickets, $stamp),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Data building (shared by index + export)
    |--------------------------------------------------------------------------
    */
    protected function buildData(Request $request): array
    {
        $user = $request->user();
        $allowed = $this->allowedDepartmentIds($user); // null = all

        [$from, $to] = $this->range($request);

        // A chosen department filter is intersected with what the user may see.
        $deptFilter = $request->department;
        if ($allowed !== null && $deptFilter && ! in_array((int) $deptFilter, $allowed)) {
            $deptFilter = null;
        }

        $base = fn () => Ticket::query()
            ->whereBetween('created_at', [$from, $to])
            ->when($allowed !== null, fn ($q) => $q->whereIn('department_id', $allowed ?: [-1]))
            ->when($deptFilter, fn ($q, $v) => $q->where('department_id', $v))
            ->when($request->technician, fn ($q, $v) => $q->where('assigned_to', $v));

        $total = (clone $base())->count();
        $closed = (clone $base())->where('status', Ticket::STATUS_CLOSED)->count();
        $open = (clone $base())->open()->count();

        $kpis = [
            'total' => $total,
            'closed' => $closed,
            'open' => $open,
            'completion_rate' => $total ? round($closed / $total * 100) : 0,
            'avg_assign_hours' => $this->avgHours($base(), 'created_at', 'assigned_at'),
            'avg_resolve_hours' => $this->avgHours($base(), 'started_at', 'resolved_at'),
        ];

        $byStatus = $this->grouped((clone $base()), 'status')
            ->map(fn ($v, $k) => ['label' => Ticket::STATUSES[$k][0] ?? $k, 'value' => $v, 'color' => Ticket::STATUSES[$k][1] ?? 'gray'])
            ->values();

        $byDept = (clone $base())->selectRaw('department_id, count(*) as total')
            ->groupBy('department_id')->with('department')->get()
            ->map(fn ($r) => ['label' => $r->department?->name ?? 'غير محدد', 'value' => (int) $r->total]);

        $byPriority = (clone $base())->selectRaw('priority_id, count(*) as total')
            ->groupBy('priority_id')->with('priority')->get()
            ->map(fn ($r) => ['label' => $r->priority?->name ?? 'غير محدد', 'value' => (int) $r->total]);

        $pauseReasons = TicketPauseLog::query()
            ->whereBetween('ticket_pause_logs.created_at', [$from, $to])
            ->when($allowed !== null, fn ($q) => $q->whereHas('ticket', fn ($t) => $t->whereIn('department_id', $allowed ?: [-1])))
            ->selectRaw('reason_code, count(*) as total')
            ->groupBy('reason_code')->get()
            ->map(fn ($r) => ['label' => TicketPauseLog::REASONS[$r->reason_code] ?? $r->reason_code, 'value' => (int) $r->total]);

        $technicians = (clone $base())
            ->whereNotNull('assigned_to')
            ->selectRaw('assigned_to, count(*) as total, sum(status = ?) as closed', [Ticket::STATUS_CLOSED])
            ->groupBy('assigned_to')->with('technician')->get()
            ->map(fn ($r) => [
                'name' => $r->technician?->name ?? '—',
                'total' => (int) $r->total,
                'closed' => (int) $r->closed,
            ])->sortByDesc('total')->values();

        $tickets = (clone $base())
            ->with(['department', 'priority', 'creator', 'technician'])
            ->latest()->get();

        // Department options the user is allowed to filter by.
        $departments = Department::query()
            ->when($allowed !== null, fn ($q) => $q->whereIn('id', $allowed ?: [-1]))
            ->orderBy('name')->get();

        $technicianList = User::role(User::ROLE_TECHNICIAN)
            ->when($allowed !== null, fn ($q) => $q->whereIn('department_id', $allowed ?: [-1]))
            ->orderBy('name')->get();

        return [
            'kpis' => $kpis,
            'byStatus' => $byStatus,
            'byDept' => $byDept,
            'byPriority' => $byPriority,
            'pauseReasons' => $pauseReasons,
            'technicians' => $technicians,
            'tickets' => $tickets,
            'departments' => $departments,
            'technicianList' => $technicianList,
            'scopeLabel' => $allowed === null ? 'كل الأقسام' : $departments->pluck('name')->implode('، '),
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'department' => $deptFilter,
                'technician' => $request->technician,
            ],
        ];
    }

    /** null = all departments (admin); array of ids = restricted (department head). */
    protected function allowedDepartmentIds(User $user): ?array
    {
        if ($user->isAdmin()) {
            return null;
        }

        return $user->headedDepartments()->pluck('id')->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Format writers
    |--------------------------------------------------------------------------
    */
    protected function pdf(array $data, string $stamp)
    {
        $tempDir = storage_path('app/mpdf');
        if (! is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => $tempDir,
            'default_font' => 'dejavusans',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->WriteHTML(view('reports.pdf', $data)->render());

        return response($mpdf->Output("tickets_report_{$stamp}.pdf", \Mpdf\Output\Destination::STRING_RETURN), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=tickets_report_{$stamp}.pdf",
        ]);
    }

    protected function csv($tickets, string $stamp): StreamedResponse
    {
        return response()->streamDownload(function () use ($tickets) {
            $out = fopen('php://output', 'w');
            fprintf($out, "\xEF\xBB\xBF");
            fputcsv($out, ['رقم التذكرة', 'العنوان', 'القسم', 'الأولوية', 'الحالة', 'مقدم الطلب', 'الفني', 'الإنجاز %', 'تاريخ الإنشاء', 'تاريخ الإغلاق']);
            foreach ($tickets as $t) {
                fputcsv($out, [
                    $t->ticket_number, $t->title, $t->department?->name, $t->priority?->name,
                    $t->statusLabel(), $t->creator?->name, $t->technician?->name, $t->progress,
                    $t->created_at?->format('Y-m-d'), $t->closed_at?->format('Y-m-d'),
                ]);
            }
            fclose($out);
        }, "tickets_report_{$stamp}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */
    protected function range(Request $request): array
    {
        $from = $request->from ? \Carbon\Carbon::parse($request->from)->startOfDay() : now()->subDays(30)->startOfDay();
        $to = $request->to ? \Carbon\Carbon::parse($request->to)->endOfDay() : now()->endOfDay();

        return [$from, $to];
    }

    protected function grouped($query, string $column)
    {
        return $query->selectRaw("$column, count(*) as total")->groupBy($column)->pluck('total', $column);
    }

    protected function avgHours($query, string $start, string $end): float
    {
        $avg = $query->whereNotNull($start)->whereNotNull($end)
            ->selectRaw("AVG(TIMESTAMPDIFF(MINUTE, $start, $end)) as m")
            ->value('m');

        return $avg ? round($avg / 60, 1) : 0;
    }
}
