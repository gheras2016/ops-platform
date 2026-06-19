<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $base = fn () => Ticket::query()->visibleTo($user);

        // KPI cards
        $kpis = [
            'open' => (clone $base())->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_ASSIGNED])->count(),
            'in_progress' => (clone $base())->whereIn('status', [Ticket::STATUS_ACCEPTED, Ticket::STATUS_IN_PROGRESS])->count(),
            'paused' => (clone $base())->where('status', Ticket::STATUS_PAUSED)->count(),
            'awaiting_approval' => (clone $base())->where('status', Ticket::STATUS_RESOLVED)->count(),
            'overdue' => (clone $base())->overdue()->count(),
            'closed_this_month' => (clone $base())->where('status', Ticket::STATUS_CLOSED)
                ->whereMonth('closed_at', now()->month)->whereYear('closed_at', now()->year)->count(),
        ];

        // Status distribution (for donut chart)
        $statusCounts = (clone $base())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $statusChart = [];
        foreach (Ticket::STATUSES as $code => [$label, $color]) {
            $statusChart[] = ['label' => $label, 'color' => $color, 'value' => (int) ($statusCounts[$code] ?? 0)];
        }

        // Tickets per department (bar chart)
        $deptChart = (clone $base())
            ->selectRaw('department_id, count(*) as total')
            ->groupBy('department_id')
            ->with('department')
            ->get()
            ->map(fn ($r) => ['label' => $r->department?->name ?? 'غير محدد', 'value' => (int) $r->total]);

        // 14-day trend (created vs closed)
        $trend = $this->buildTrend($user);

        // Recent tickets
        $recent = (clone $base())
            ->with(['department', 'priority', 'technician', 'creator'])
            ->latest()
            ->limit(8)
            ->get();

        // Role-specific work lists
        $myWork = $user->isTechnician()
            ? Ticket::with(['department', 'priority', 'creator'])
                ->where('assigned_to', $user->id)
                ->open()
                ->latest()
                ->limit(6)
                ->get()
            : collect();

        $incomingQueue = ($user->isDepartmentHead() || $user->isCompanyAdmin())
            ? (clone $base())->with(['priority', 'creator', 'department'])
                ->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_RESOLVED])
                ->latest()
                ->limit(6)
                ->get()
            : collect();

        return view('dashboard.index', compact(
            'kpis', 'statusChart', 'deptChart', 'trend', 'recent', 'myWork', 'incomingQueue'
        ));
    }

    protected function buildTrend(User $user): array
    {
        $days = collect(range(13, 0))->map(fn ($d) => now()->subDays($d)->toDateString());

        $created = Ticket::visibleTo($user)
            ->whereDate('created_at', '>=', now()->subDays(13)->toDateString())
            ->selectRaw('DATE(created_at) as d, count(*) as c')
            ->groupBy('d')->pluck('c', 'd');

        $closed = Ticket::visibleTo($user)
            ->whereNotNull('closed_at')
            ->whereDate('closed_at', '>=', now()->subDays(13)->toDateString())
            ->selectRaw('DATE(closed_at) as d, count(*) as c')
            ->groupBy('d')->pluck('c', 'd');

        return [
            'labels' => $days->map(fn ($d) => \Carbon\Carbon::parse($d)->format('m/d'))->all(),
            'created' => $days->map(fn ($d) => (int) ($created[$d] ?? 0))->all(),
            'closed' => $days->map(fn ($d) => (int) ($closed[$d] ?? 0))->all(),
        ];
    }
}
