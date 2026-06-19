@extends('layouts.app')
@section('title', 'لوحة التحكم')
@section('page-title', 'لوحة التحكم')
@section('page-sub', 'نظرة عامة على أداء عمليات الصيانة')

@section('content')
@php($u = auth()->user())

{{-- KPI cards --}}
<div class="stats-grid mb-5">
    <div class="stat-card">
        <div class="stat-icon soft-gray"><i class="fa-solid fa-folder-open"></i></div>
        <div class="stat-value">{{ $kpis['open'] }}</div>
        <div class="stat-label">تذاكر مفتوحة / بانتظار الإسناد</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon soft-amber"><i class="fa-solid fa-spinner"></i></div>
        <div class="stat-value">{{ $kpis['in_progress'] }}</div>
        <div class="stat-label">قيد التنفيذ</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon soft-orange"><i class="fa-solid fa-pause"></i></div>
        <div class="stat-value">{{ $kpis['paused'] }}</div>
        <div class="stat-label">متوقفة مؤقتًا</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon soft-teal"><i class="fa-solid fa-clipboard-check"></i></div>
        <div class="stat-value">{{ $kpis['awaiting_approval'] }}</div>
        <div class="stat-label">بانتظار الاعتماد</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon soft-red"><i class="fa-solid fa-clock"></i></div>
        <div class="stat-value">{{ $kpis['overdue'] }}</div>
        <div class="stat-label">متأخرة عن الموعد</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon soft-green"><i class="fa-solid fa-circle-check"></i></div>
        <div class="stat-value">{{ $kpis['closed_this_month'] }}</div>
        <div class="stat-label">مغلقة هذا الشهر</div>
    </div>
</div>

<div class="detail-grid mb-5">
    {{-- Trend chart --}}
    <div class="card">
        <div class="card-header"><h3>اتجاه التذاكر — آخر ١٤ يومًا</h3><span class="sub">المنشأة مقابل المغلقة</span></div>
        <div class="card-body"><canvas id="trendChart" height="110"></canvas></div>
    </div>

    {{-- Status donut --}}
    <div class="card">
        <div class="card-header"><h3>توزيع الحالات</h3></div>
        <div class="card-body"><canvas id="statusChart" height="200"></canvas></div>
    </div>
</div>

<div class="detail-grid">
    {{-- Role-specific work list --}}
    <div class="card">
        @if($u->isTechnician())
            <div class="card-header"><h3><i class="fa-solid fa-screwdriver-wrench text-muted"></i> مهامي الحالية</h3><a href="{{ route('tickets.index') }}" class="sub spacer" style="margin-right:auto">عرض الكل</a></div>
            <div class="card-body">
                @forelse($myWork as $t)
                    @include('tickets._row_compact', ['t' => $t])
                @empty
                    <x-empty icon="fa-mug-hot" title="لا توجد مهام حالية" sub="ليست لديك تذاكر مسندة قيد العمل" />
                @endforelse
            </div>
        @elseif($u->isDepartmentHead() || $u->isCompanyAdmin())
            <div class="card-header"><h3><i class="fa-solid fa-inbox text-muted"></i> قائمة الانتظار (إسناد / اعتماد)</h3><a href="{{ route('tickets.index') }}" class="sub" style="margin-right:auto">عرض الكل</a></div>
            <div class="card-body">
                @forelse($incomingQueue as $t)
                    @include('tickets._row_compact', ['t' => $t])
                @empty
                    <x-empty icon="fa-check-double" title="لا شيء بانتظارك" sub="لا توجد تذاكر بحاجة لإجراء" />
                @endforelse
            </div>
        @else
            <div class="card-header"><h3><i class="fa-solid fa-ticket text-muted"></i> أحدث بلاغاتي</h3><a href="{{ route('tickets.index') }}" class="sub" style="margin-right:auto">عرض الكل</a></div>
            <div class="card-body">
                @forelse($recent as $t)
                    @include('tickets._row_compact', ['t' => $t])
                @empty
                    <x-empty icon="fa-ticket" title="لا توجد بلاغات" sub="لم ترفع أي بلاغ بعد">
                        <a href="{{ route('tickets.create') }}" class="btn btn-primary btn-sm mt-3"><i class="fa-solid fa-plus"></i> رفع بلاغ</a>
                    </x-empty>
                @endforelse
            </div>
        @endif
    </div>

    {{-- Departments breakdown --}}
    <div class="card">
        <div class="card-header"><h3>التذاكر حسب القسم</h3></div>
        <div class="card-body"><canvas id="deptChart" height="220"></canvas></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const trend = @json($trend);
const statusData = @json($statusChart);
const deptData = @json($deptChart);
const palette = {gray:'#64748b',slate:'#475569',indigo:'#6366f1',blue:'#2563eb',amber:'#d97706',orange:'#ea580c',teal:'#0d9488',green:'#16a34a',red:'#dc2626'};
Chart.defaults.font.family = 'Tajawal, sans-serif';
Chart.defaults.color = '#64748b';

new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: { labels: trend.labels, datasets: [
        { label: 'منشأة', data: trend.created, borderColor: '#4f46e5', backgroundColor: 'rgba(79,70,229,.1)', fill: true, tension: .4 },
        { label: 'مغلقة', data: trend.closed, borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,.08)', fill: true, tension: .4 },
    ]},
    options: { plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
});

new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: { labels: statusData.map(s => s.label), datasets: [{ data: statusData.map(s => s.value), backgroundColor: statusData.map(s => palette[s.color] || '#64748b'), borderWidth: 0 }] },
    options: { cutout: '64%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } } } }
});

new Chart(document.getElementById('deptChart'), {
    type: 'bar',
    data: { labels: deptData.map(d => d.label), datasets: [{ data: deptData.map(d => d.value), backgroundColor: '#6366f1', borderRadius: 8, barThickness: 22 }] },
    options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } }
});
</script>
@endpush
