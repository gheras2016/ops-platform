@extends('layouts.app')
@section('title', 'الاشتراكات')
@section('page-title', 'الاشتراكات')
@section('page-sub', 'إدارة الفترات التجريبية واشتراكات الشركات')

@php
    $colors = ['blue' => '#2563eb', 'green' => '#16a34a', 'amber' => '#d97706', 'red' => '#dc2626', 'gray' => '#64748b'];
    $pill = function ($label, $color) use ($colors) {
        $c = $colors[$color] ?? $colors['gray'];
        return '<span style="display:inline-block;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700;color:'
            . $c . ';background:' . $c . '1f">' . e($label) . '</span>';
    };
@endphp

@section('content')
<div class="page-head">
    <div class="titles"><h2>الاشتراكات</h2><p>{{ $total }} شركة</p></div>
</div>

@if(session('success'))<div class="alert alert-success" style="margin-bottom:16px"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger" style="margin-bottom:16px"><i class="fa-solid fa-circle-exclamation"></i> {{ $errors->first() }}</div>@endif

{{-- KPI cards --}}
<div class="grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin-bottom:18px">
    @php $kpis = [
        ['إجمالي الشركات', $total, '#0f172a'],
        ['تجريبي', $counts['trial'] ?? 0, $colors['blue']],
        ['نشط', $counts['active'] ?? 0, $colors['green']],
        ['مهلة سماح', $counts['grace'] ?? 0, $colors['amber']],
        ['موقوف', $counts['suspended'] ?? 0, $colors['red']],
        ['الإيراد الشهري التقديري', number_format($mrr, 0) . ' ر.س', '#4f46e5'],
    ]; @endphp
    @foreach($kpis as [$label, $value, $color])
        <div class="card" style="padding:16px">
            <div style="font-size:22px;font-weight:800;color:{{ $color }}">{{ $value }}</div>
            <div style="color:var(--text-muted,#64748b);font-size:12px;margin-top:2px">{{ $label }}</div>
        </div>
    @endforeach
</div>

<div class="card"><div class="table-wrap"><table class="table">
    <thead><tr><th>الشركة</th><th>الحالة</th><th>الباقة</th><th>المتبقّي</th><th>الإجراءات</th></tr></thead>
    <tbody>
        @forelse($companies as $c)
            @php $days = $c->daysRemaining(); @endphp
            <tr>
                <td><div class="cell-title">{{ $c->name }}</div><div class="cell-sub">{{ $c->code }} · {{ $c->email }}</div></td>
                <td>{!! $pill($c->subStatusLabel(), $c->subStatusColor()) !!}</td>
                <td>{{ $c->plan?->name ?? '—' }}</td>
                <td>
                    @if(is_null($days))
                        <span style="color:#64748b">بلا انتهاء</span>
                    @elseif($days < 0)
                        <span style="color:{{ $colors['red'] }};font-weight:700">منتهٍ</span>
                    @else
                        <span style="font-weight:700;color:{{ $days <= 3 ? $colors['red'] : ($days <= 7 ? $colors['amber'] : '#0f172a') }}">{{ $days }} يوم</span>
                    @endif
                </td>
                <td>
                    <div class="flex" style="gap:6px;flex-wrap:wrap;align-items:center">
                        {{-- Activate / renew --}}
                        <form method="POST" action="{{ route('subscriptions.activate', $c) }}" class="flex" style="gap:4px;align-items:center">
                            @csrf
                            <select name="plan_id" class="form-control" style="width:auto;padding:4px 8px;font-size:12px" required>
                                @foreach($plans as $p)<option value="{{ $p->id }}">{{ $p->name }} — {{ number_format($p->price,0) }}</option>@endforeach
                            </select>
                            <button class="btn btn-primary" style="padding:5px 10px;font-size:12px"><i class="fa-solid fa-check"></i> تفعيل</button>
                        </form>
                        {{-- Extend trial --}}
                        <form method="POST" action="{{ route('subscriptions.extend', $c) }}" class="flex" style="gap:4px;align-items:center">
                            @csrf
                            <input type="number" name="days" value="7" min="1" max="365" class="form-control" style="width:56px;padding:4px 6px;font-size:12px">
                            <button class="btn" style="padding:5px 10px;font-size:12px;border:1px solid var(--border,#e2e8f0)"><i class="fa-solid fa-hourglass-half"></i> تجربة</button>
                        </form>
                        {{-- Suspend --}}
                        @if($c->subscription_status !== 'suspended')
                        <form method="POST" action="{{ route('subscriptions.suspend', $c) }}" onsubmit="return confirm('إيقاف هذه الشركة؟')">
                            @csrf
                            <button class="icon-btn" style="width:32px;height:32px;color:{{ $colors['red'] }}" title="إيقاف"><i class="fa-solid fa-ban"></i></button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="5"><x-empty icon="fa-credit-card" title="لا توجد شركات" /></td></tr>
        @endforelse
    </tbody>
</table></div></div>
<div class="pagination-wrap">{{ $companies->links() }}</div>
@endsection
