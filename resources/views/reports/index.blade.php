@extends('layouts.app')
@section('title', 'التقارير والتحليلات')
@section('page-title', 'التقارير والتحليلات')
@section('page-sub', 'مؤشرات أداء عمليات الصيانة')

@section('content')
<div class="page-head">
    <div class="titles"><h2>التقارير والتحليلات</h2><p>النطاق: {{ $scopeLabel }} · الفترة: {{ $filters['from'] }} إلى {{ $filters['to'] }}</p></div>
    <div class="actions">
        <a href="{{ route('reports.export', array_merge(['format' => 'pdf'], request()->query())) }}" class="btn btn-danger"><i class="fa-solid fa-file-pdf"></i> PDF</a>
        <a href="{{ route('reports.export', array_merge(['format' => 'xlsx'], request()->query())) }}" class="btn btn-success"><i class="fa-solid fa-file-excel"></i> Excel</a>
        <a href="{{ route('reports.export', array_merge(['format' => 'csv'], request()->query())) }}" class="btn btn-outline"><i class="fa-solid fa-file-csv"></i> CSV</a>
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="card card-body filter-bar mb-5">
    <div class="form-group"><label class="form-label">من</label><input type="date" name="from" class="form-control" value="{{ $filters['from'] }}"></div>
    <div class="form-group"><label class="form-label">إلى</label><input type="date" name="to" class="form-control" value="{{ $filters['to'] }}"></div>
    <div class="form-group"><label class="form-label">القسم</label>
        <select name="department" class="form-select"><option value="">الكل</option>
            @foreach($departments as $d)<option value="{{ $d->id }}" @selected($filters['department']==$d->id)>{{ $d->name }}</option>@endforeach
        </select>
    </div>
    <div class="form-group"><label class="form-label">الفني</label>
        <select name="technician" class="form-select"><option value="">الكل</option>
            @foreach($technicianList as $t)<option value="{{ $t->id }}" @selected($filters['technician']==$t->id)>{{ $t->name }}</option>@endforeach
        </select>
    </div>
    <div class="form-group"><label class="form-label">&nbsp;</label><button class="btn btn-primary"><i class="fa-solid fa-filter"></i> تطبيق</button></div>
</form>

{{-- KPIs --}}
<div class="stats-grid mb-5">
    <div class="stat-card"><div class="stat-icon soft-indigo"><i class="fa-solid fa-layer-group"></i></div><div class="stat-value">{{ $kpis['total'] }}</div><div class="stat-label">إجمالي التذاكر</div></div>
    <div class="stat-card"><div class="stat-icon soft-green"><i class="fa-solid fa-circle-check"></i></div><div class="stat-value">{{ $kpis['closed'] }}</div><div class="stat-label">مغلقة</div></div>
    <div class="stat-card"><div class="stat-icon soft-amber"><i class="fa-solid fa-spinner"></i></div><div class="stat-value">{{ $kpis['open'] }}</div><div class="stat-label">قيد المعالجة</div></div>
    <div class="stat-card"><div class="stat-icon soft-teal"><i class="fa-solid fa-percent"></i></div><div class="stat-value">{{ $kpis['completion_rate'] }}%</div><div class="stat-label">معدل الإنجاز</div></div>
    <div class="stat-card"><div class="stat-icon soft-blue"><i class="fa-solid fa-stopwatch"></i></div><div class="stat-value">{{ $kpis['avg_assign_hours'] }}</div><div class="stat-label">متوسط ساعات الإسناد</div></div>
    <div class="stat-card"><div class="stat-icon soft-orange"><i class="fa-solid fa-hourglass-half"></i></div><div class="stat-value">{{ $kpis['avg_resolve_hours'] }}</div><div class="stat-label">متوسط ساعات الحل</div></div>
</div>

<div class="cols-2 mb-5">
    <div class="card"><div class="card-header"><h3>التذاكر حسب الحالة</h3></div><div class="card-body"><canvas id="statusChart" height="200"></canvas></div></div>
    <div class="card"><div class="card-header"><h3>التذاكر حسب القسم</h3></div><div class="card-body"><canvas id="deptChart" height="200"></canvas></div></div>
</div>

<div class="cols-2 mb-5">
    <div class="card"><div class="card-header"><h3>التذاكر حسب الأولوية</h3></div><div class="card-body"><canvas id="priorityChart" height="200"></canvas></div></div>
    <div class="card"><div class="card-header"><h3>أسباب الإيقاف المؤقت</h3></div><div class="card-body">
        @if($pauseReasons->isEmpty())<x-empty icon="fa-pause" title="لا توجد حالات إيقاف" />@else<canvas id="pauseChart" height="200"></canvas>@endif
    </div></div>
</div>

{{-- Technician performance --}}
<div class="card">
    <div class="card-header"><h3><i class="fa-solid fa-ranking-star text-muted"></i> أداء الفنيين</h3></div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>الفني</th><th>إجمالي التذاكر</th><th>المغلقة</th><th>معدل الإنجاز</th></tr></thead>
            <tbody>
                @forelse($technicians as $t)
                    <tr>
                        <td class="cell-title">{{ $t['name'] }}</td>
                        <td>{{ $t['total'] }}</td>
                        <td>{{ $t['closed'] }}</td>
                        <td style="min-width:160px">
                            @php($rate = $t['total'] ? round($t['closed']/$t['total']*100) : 0)
                            <div class="flex items-center gap-2"><div class="progress" style="flex:1"><div class="progress-bar green" style="width:{{ $rate }}%"></div></div><span class="text-sm fw-700">{{ $rate }}%</span></div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4"><x-empty icon="fa-users" title="لا توجد بيانات" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
const palette = {gray:'#64748b',slate:'#475569',indigo:'#6366f1',blue:'#2563eb',amber:'#d97706',orange:'#ea580c',teal:'#0d9488',green:'#16a34a',red:'#dc2626'};
Chart.defaults.font.family = 'Tajawal, sans-serif'; Chart.defaults.color = '#64748b';
const byStatus = @json($byStatus); const byDept = @json($byDept); const byPriority = @json($byPriority); const pauseReasons = @json($pauseReasons);

new Chart(document.getElementById('statusChart'), { type:'doughnut',
    data:{ labels: byStatus.map(s=>s.label), datasets:[{ data: byStatus.map(s=>s.value), backgroundColor: byStatus.map(s=>palette[s.color]||'#64748b'), borderWidth:0 }] },
    options:{ cutout:'62%', plugins:{ legend:{ position:'bottom', labels:{boxWidth:12,padding:10} } } } });

new Chart(document.getElementById('deptChart'), { type:'bar',
    data:{ labels: byDept.map(d=>d.label), datasets:[{ data: byDept.map(d=>d.value), backgroundColor:'#6366f1', borderRadius:8 }] },
    options:{ plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,ticks:{precision:0}}} } });

new Chart(document.getElementById('priorityChart'), { type:'bar',
    data:{ labels: byPriority.map(d=>d.label), datasets:[{ data: byPriority.map(d=>d.value), backgroundColor:'#0d9488', borderRadius:8 }] },
    options:{ indexAxis:'y', plugins:{legend:{display:false}}, scales:{x:{beginAtZero:true,ticks:{precision:0}}} } });

if (document.getElementById('pauseChart')) new Chart(document.getElementById('pauseChart'), { type:'pie',
    data:{ labels: pauseReasons.map(d=>d.label), datasets:[{ data: pauseReasons.map(d=>d.value), backgroundColor:['#ea580c','#d97706','#dc2626','#6366f1','#0d9488','#64748b'], borderWidth:0 }] },
    options:{ plugins:{ legend:{ position:'bottom', labels:{boxWidth:12,padding:10} } } } });
</script>
@endpush
