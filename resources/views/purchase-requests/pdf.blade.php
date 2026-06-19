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
        .meta { width: 100%; margin-bottom: 14px; }
        .meta td { padding: 4px 6px; font-size: 10.5px; }
        .meta .lbl { color: #64748b; width: 22%; }
        .meta .val { font-weight: bold; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.items th { background: #4f46e5; color: #fff; padding: 7px; text-align: right; font-size: 10px; }
        table.items td { border: 1px solid #e2e8f0; padding: 6px 8px; font-size: 10px; }
        .total-row td { background: #f1f5f9; font-weight: bold; }
        .section { font-size: 13px; font-weight: bold; margin: 12px 0 6px; color: #334155; }
        table.appr { width: 100%; border-collapse: collapse; }
        table.appr th { background: #f1f5f9; padding: 6px; font-size: 10px; text-align: right; border: 1px solid #e2e8f0; }
        table.appr td { border: 1px solid #e2e8f0; padding: 8px; font-size: 10px; height: 34px; }
        .badge { padding: 2px 8px; border-radius: 8px; background:#eef2ff; color:#4f46e5; font-size:10px; }
    </style>
</head>
<body>
    <div class="head">
        <table style="width:100%"><tr>
            <td style="width:60%"><h1>طلب شراء / أمر توريد</h1><div class="muted">رقم: {{ $pr->request_number }}</div></td>
            <td style="text-align:left"><div class="co">{{ $pr->company?->name ?? 'OPS Platform' }}</div><div class="muted">تاريخ الإصدار: {{ now()->format('Y-m-d') }}</div></td>
        </tr></table>
    </div>

    <table class="meta">
        <tr>
            <td class="lbl">القسم الطالب</td><td class="val">{{ $pr->department?->name ?? '—' }}</td>
            <td class="lbl">نوع الشراء</td><td class="val">{{ $pr->typeLabel() }}</td>
        </tr>
        <tr>
            <td class="lbl">مُقدّم الطلب</td><td class="val">{{ $pr->requester?->name ?? '—' }}</td>
            <td class="lbl">الحالة</td><td class="val">{{ $pr->statusLabel() }}</td>
        </tr>
        <tr>
            <td class="lbl">المورّد المقترح</td><td class="val">{{ $pr->supplier ?? '—' }}</td>
            <td class="lbl">التذكرة المرتبطة</td><td class="val">{{ $pr->ticket?->ticket_number ?? '—' }}</td>
        </tr>
        @if($pr->justification)
        <tr><td class="lbl">المبرّر</td><td class="val" colspan="3" style="font-weight:normal">{{ $pr->justification }}</td></tr>
        @endif
    </table>

    <div class="section">الأصناف المطلوبة</div>
    <table class="items">
        <tr><th style="width:6%">#</th><th>الصنف</th><th style="width:12%">الكمية</th><th style="width:16%">سعر الوحدة</th><th style="width:18%">الإجمالي</th></tr>
        @foreach($pr->items as $i => $it)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $it->sparePart?->name ?? $it->custom_name }}@if(!$it->spare_part_id) <span class="badge">خارج الكتالوج</span>@endif</td>
                <td>{{ $it->quantity }}</td>
                <td>{{ $it->unit_price ? number_format($it->unit_price, 2) : '—' }}</td>
                <td>{{ $it->unit_price ? number_format($it->unit_price * $it->quantity, 2) : '—' }}</td>
            </tr>
        @endforeach
        <tr class="total-row"><td colspan="4" style="text-align:left">الإجمالي التقديري</td><td>{{ number_format($pr->totalEstimate(), 2) }}</td></tr>
    </table>

    <div class="section">سلسلة الاعتماد والتوقيعات</div>
    <table class="appr">
        <tr><th>المرحلة</th><th>القرار</th><th>المعتمد</th><th>التاريخ</th><th>التوقيع</th></tr>
        @forelse($pr->approvals as $a)
            <tr>
                <td>{{ $a->stageLabel() }}</td>
                <td>{{ $a->decisionLabel() }}</td>
                <td>{{ $a->approver?->name ?? '—' }}</td>
                <td>{{ $a->decided_at?->format('Y-m-d') }}</td>
                <td></td>
            </tr>
        @empty
            <tr><td colspan="5" style="text-align:center">— لم تبدأ سلسلة الاعتماد —</td></tr>
        @endforelse
        @if($pr->received_at)
            <tr><td>التنفيذ / الاستلام</td><td>تم</td><td>—</td><td>{{ $pr->received_at->format('Y-m-d') }}</td><td></td></tr>
        @endif
    </table>
</body>
</html>
