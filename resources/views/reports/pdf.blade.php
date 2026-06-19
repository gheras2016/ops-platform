<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: dejavusans, sans-serif; color: #1e293b; font-size: 11px; }
        h1 { font-size: 18px; color: #4f46e5; margin: 0 0 2px; }
        .muted { color: #64748b; font-size: 10px; }
        .head { border-bottom: 2px solid #4f46e5; padding-bottom: 8px; margin-bottom: 14px; }
        .kpis { width: 100%; margin-bottom: 16px; }
        .kpis td { background: #f1f5f9; border: 3px solid #fff; padding: 8px 10px; width: 16%; text-align: center; }
        .kpi-val { font-size: 16px; font-weight: bold; color: #0f172a; }
        .kpi-lbl { font-size: 9px; color: #64748b; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table.data th { background: #4f46e5; color: #fff; padding: 6px 8px; text-align: right; font-size: 10px; }
        table.data td { border-bottom: 1px solid #e2e8f0; padding: 5px 8px; font-size: 10px; }
        .section-title { font-size: 13px; font-weight: bold; margin: 10px 0 6px; color: #334155; }
        .two-col { width: 100%; }
        .two-col > div { width: 49%; display: inline-block; vertical-align: top; }
    </style>
</head>
<body>
    <div class="head">
        <h1>تقرير عمليات الصيانة</h1>
        <div class="muted">
            النطاق: {{ $scopeLabel }} &nbsp;|&nbsp; الفترة: {{ $filters['from'] }} إلى {{ $filters['to'] }}
            &nbsp;|&nbsp; تاريخ الإصدار: {{ now()->format('Y-m-d H:i') }}
        </div>
    </div>

    {{-- KPIs --}}
    <table class="kpis">
        <tr>
            <td><div class="kpi-val">{{ $kpis['total'] }}</div><div class="kpi-lbl">إجمالي التذاكر</div></td>
            <td><div class="kpi-val">{{ $kpis['closed'] }}</div><div class="kpi-lbl">مغلقة</div></td>
            <td><div class="kpi-val">{{ $kpis['open'] }}</div><div class="kpi-lbl">قيد المعالجة</div></td>
            <td><div class="kpi-val">{{ $kpis['completion_rate'] }}%</div><div class="kpi-lbl">معدل الإنجاز</div></td>
            <td><div class="kpi-val">{{ $kpis['avg_assign_hours'] }}</div><div class="kpi-lbl">متوسط ساعات الإسناد</div></td>
            <td><div class="kpi-val">{{ $kpis['avg_resolve_hours'] }}</div><div class="kpi-lbl">متوسط ساعات الحل</div></td>
        </tr>
    </table>

    <div class="two-col">
        <div>
            <div class="section-title">التذاكر حسب الحالة</div>
            <table class="data">
                <tr><th>الحالة</th><th>العدد</th></tr>
                @foreach($byStatus as $r)<tr><td>{{ $r['label'] }}</td><td>{{ $r['value'] }}</td></tr>@endforeach
            </table>
        </div>
        <div>
            <div class="section-title">التذاكر حسب القسم</div>
            <table class="data">
                <tr><th>القسم</th><th>العدد</th></tr>
                @foreach($byDept as $r)<tr><td>{{ $r['label'] }}</td><td>{{ $r['value'] }}</td></tr>@endforeach
            </table>
        </div>
    </div>

    <div class="section-title">أداء الفنيين</div>
    <table class="data">
        <tr><th>الفني</th><th>إجمالي التذاكر</th><th>المغلقة</th><th>معدل الإنجاز</th></tr>
        @forelse($technicians as $t)
            <tr><td>{{ $t['name'] }}</td><td>{{ $t['total'] }}</td><td>{{ $t['closed'] }}</td><td>{{ $t['total'] ? round($t['closed']/$t['total']*100) : 0 }}%</td></tr>
        @empty
            <tr><td colspan="4">لا توجد بيانات</td></tr>
        @endforelse
    </table>

    <div class="section-title">تفاصيل التذاكر ({{ $tickets->count() }})</div>
    <table class="data">
        <tr><th>الرقم</th><th>العنوان</th><th>القسم</th><th>الأولوية</th><th>الحالة</th><th>الفني</th><th>الإنجاز</th></tr>
        @foreach($tickets as $t)
            <tr>
                <td>{{ $t->ticket_number }}</td>
                <td>{{ $t->title }}</td>
                <td>{{ $t->department?->name }}</td>
                <td>{{ $t->priority?->name }}</td>
                <td>{{ $t->statusLabel() }}</td>
                <td>{{ $t->technician?->name ?? '—' }}</td>
                <td>{{ $t->progress }}%</td>
            </tr>
        @endforeach
    </table>
</body>
</html>
