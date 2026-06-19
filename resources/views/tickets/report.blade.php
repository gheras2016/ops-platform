<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: dejavusans, sans-serif; color: #1e293b; font-size: 11px; }
        .head { border-bottom: 2px solid #4f46e5; padding-bottom: 10px; margin-bottom: 14px; }
        .head h1 { font-size: 18px; color: #4f46e5; margin: 0; }
        .head .co { font-size: 13px; font-weight: bold; }
        .muted { color: #64748b; font-size: 10px; }
        .section { font-size: 13px; font-weight: bold; margin: 14px 0 6px; color: #334155; border-right: 3px solid #4f46e5; padding-right: 8px; }
        table.info { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        table.info td { padding: 5px 7px; font-size: 10.5px; border: 1px solid #eef2f7; }
        table.info .lbl { color: #64748b; width: 16%; background: #f8fafc; }
        table.info .val { font-weight: bold; width: 34%; }
        .desc { background: #f8fafc; border: 1px solid #eef2f7; border-radius: 6px; padding: 8px 10px; font-size: 10.5px; line-height: 1.7; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        table.data th { background: #4f46e5; color: #fff; padding: 6px 8px; text-align: right; font-size: 10px; }
        table.data td { border: 1px solid #e2e8f0; padding: 6px 8px; font-size: 10px; }
        .total-row td { background: #eef2ff; font-weight: bold; }
        .tl td { border: none; padding: 3px 6px; font-size: 10px; vertical-align: top; }
        .tl .dot { color: #4f46e5; width: 14px; }
        .tl .when { color: #94a3b8; width: 28%; }
        .sign { width: 100%; margin-top: 26px; }
        .sign td { width: 50%; padding: 6px; font-size: 10.5px; }
        .sign .box { border-top: 1px solid #cbd5e1; margin-top: 36px; padding-top: 5px; color: #64748b; }
    </style>
</head>
<body>
    <div class="head">
        <table style="width:100%"><tr>
            <td style="width:62%"><h1>تقرير بلاغ صيانة</h1><div class="muted">رقم البلاغ: {{ $ticket->ticket_number }}</div></td>
            <td style="text-align:left"><div class="co">{{ $ticket->company?->name ?? 'OPS Platform' }}</div><div class="muted">تاريخ الإصدار: {{ now()->format('Y-m-d H:i') }}</div></td>
        </tr></table>
    </div>

    <div class="section">بيانات البلاغ</div>
    <table class="info">
        <tr><td class="lbl">العنوان</td><td class="val" colspan="3">{{ $ticket->title }}</td></tr>
        <tr>
            <td class="lbl">القسم</td><td class="val">{{ $ticket->department?->name ?? '—' }}</td>
            <td class="lbl">الحالة</td><td class="val">{{ $ticket->statusLabel() }}</td>
        </tr>
        <tr>
            <td class="lbl">الأولوية</td><td class="val">{{ $ticket->priority?->name ?? '—' }}</td>
            <td class="lbl">الموقع</td><td class="val">{{ $ticket->location?->full_path ?? $ticket->location?->name ?? '—' }}@if($ticket->location_detail) — {{ $ticket->location_detail }}@endif</td>
        </tr>
        <tr>
            <td class="lbl">مقدّم البلاغ</td><td class="val">{{ $ticket->creator?->name ?? '—' }}</td>
            <td class="lbl">الفني المنفّذ</td><td class="val">{{ $ticket->technician?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">الأصل</td><td class="val">{{ $ticket->asset?->name ?? '—' }}</td>
            <td class="lbl">نسبة الإنجاز</td><td class="val">{{ $ticket->progress }}%</td>
        </tr>
        <tr>
            <td class="lbl">تاريخ الفتح</td><td class="val">{{ $ticket->created_at?->format('Y-m-d H:i') }}</td>
            <td class="lbl">تاريخ الإغلاق</td><td class="val">{{ $ticket->closed_at?->format('Y-m-d H:i') ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">مدة التنفيذ</td><td class="val">{{ $ticket->handlingDuration() ?? '—' }}</td>
            <td class="lbl">اعتمد الإغلاق</td><td class="val">{{ $ticket->closer?->name ?? '—' }}</td>
        </tr>
    </table>

    @if($ticket->description)
        <div class="section">وصف العطل</div>
        <div class="desc">{{ $ticket->description }}</div>
    @endif

    <div class="section">الأعمال المنفّذة (الخط الزمني)</div>
    <table>
        @foreach($ticket->events->sortBy('created_at') as $e)
            <tr class="tl">
                <td class="dot">●</td>
                <td>{{ $e->label() }}@if($e->note) — <span class="muted">{{ $e->note }}</span>@endif</td>
                <td class="when">{{ $e->user?->name }} · {{ $e->created_at->format('Y-m-d H:i') }}</td>
            </tr>
        @endforeach
    </table>

    @if($ticket->pauseLogs->isNotEmpty())
        <div class="section">حالات الإيقاف المؤقت</div>
        <table class="data">
            <tr><th>السبب</th><th>التفاصيل</th><th>من</th><th>إلى</th></tr>
            @foreach($ticket->pauseLogs as $p)
                <tr>
                    <td>{{ $p->reasonLabel() }}</td>
                    <td>{{ $p->reason ?? '—' }}</td>
                    <td>{{ $p->paused_at?->format('Y-m-d H:i') }}</td>
                    <td>{{ $p->resumed_at?->format('Y-m-d H:i') ?? 'مستمر' }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <div class="section">القطع والمواد المستخدمة</div>
    <table class="data">
        <tr><th style="width:6%">#</th><th>القطعة</th><th style="width:12%">الكمية</th><th style="width:18%">تكلفة الوحدة</th><th style="width:20%">الإجمالي</th></tr>
        @forelse($ticket->spareParts as $i => $sp)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $sp->displayName() }}{{ $sp->isCustom() ? ' (خارج الكاتالوج)' : '' }}</td>
                <td>{{ $sp->quantity_used }}</td>
                <td>{{ $sp->unit_cost ? number_format($sp->unit_cost, 2) : '—' }}</td>
                <td>{{ $sp->unit_cost ? number_format($sp->lineTotal(), 2) : '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="5" style="text-align:center">لم تُستخدم قطع غيار</td></tr>
        @endforelse
        @if($ticket->spareParts->isNotEmpty())
            <tr class="total-row"><td colspan="4" style="text-align:left">إجمالي تكلفة القطع</td><td>{{ number_format($ticket->partsCost(), 2) }}</td></tr>
        @endif
    </table>

    @if($ticket->resolution_note)
        <div class="section">ملخص الحل</div>
        <div class="desc">{{ $ticket->resolution_note }}</div>
    @endif

    <table class="sign">
        <tr>
            <td><div class="box">توقيع الفني المنفّذ: {{ $ticket->technician?->name ?? '' }}</div></td>
            <td><div class="box">توقيع رئيس القسم: {{ $ticket->department?->head?->name ?? '' }}</div></td>
        </tr>
    </table>
</body>
</html>
