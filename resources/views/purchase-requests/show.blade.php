@extends('layouts.app')
@section('title', $pr->request_number)
@section('page-title', 'طلب شراء')
@section('page-sub', $pr->request_number)

@section('content')
@php($user = auth()->user())
<div class="breadcrumb"><a href="{{ route('purchase-requests.index') }}">طلبات الشراء</a> <i class="fa-solid fa-chevron-left"></i> <span>{{ $pr->request_number }}</span></div>

<div class="flex items-center justify-between mb-4" style="flex-wrap:wrap;gap:10px">
    <div class="flex items-center gap-3">
        <span class="badge badge-{{ $pr->statusColor() }} badge-dot" style="font-size:14px;padding:7px 14px">{{ $pr->statusLabel() }}</span>
        <span class="badge badge-{{ $pr->isDirect() ? 'orange' : 'blue' }}">{{ $pr->typeLabel() }}</span>
    </div>
    <a href="{{ route('purchase-requests.print', $pr) }}" target="_blank" class="btn btn-outline"><i class="fa-solid fa-print"></i> طباعة PDF</a>
</div>

{{-- Actions --}}
@if(($user->can('decide', $pr)) || ($pr->canBeReceived() && $user->can('receive', $pr)))
<div class="card card-body mb-4">
    <div class="flex items-center justify-between" style="flex-wrap:wrap;gap:10px">
        <div class="fw-700"><i class="fa-solid fa-bolt soft-primary" style="padding:6px;border-radius:8px"></i> الإجراء المطلوب
            @if($pr->canDeptDecide())<span class="text-sm text-muted">(اعتماد {{ $pr->currentDept?->name }})</span>
            @elseif($pr->canFinanceDecide())<span class="text-sm text-muted">(اعتماد المالية)</span>
            @elseif($pr->canBeReceived())<span class="text-sm text-muted">(تنفيذ {{ $pr->typeLabel() }})</span>@endif
        </div>
        <div class="action-bar">
            @can('decide', $pr)
                <form action="{{ route('purchase-requests.approve', $pr) }}" method="POST">@csrf<button class="btn btn-success"><i class="fa-solid fa-check"></i> اعتماد</button></form>
                <button class="btn btn-danger" onclick="openModal('rejPr')"><i class="fa-solid fa-xmark"></i> رفض</button>
            @endcan
            @if($pr->canBeReceived() && $user->can('receive', $pr))
                <form action="{{ route('purchase-requests.receive', $pr) }}" method="POST" onsubmit="return confirm('تأكيد تنفيذ الطلب؟')">@csrf<button class="btn btn-primary"><i class="fa-solid fa-box-open"></i> تنفيذ الطلب</button></form>
            @endif
        </div>
    </div>
</div>
@endif

<div class="detail-grid">
    <div>
        <div class="card mb-4">
            <div class="card-header"><h3>الأصناف</h3><span class="sub">الإجمالي التقديري: {{ number_format($pr->totalEstimate(), 2) }}</span></div>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>الصنف</th><th>الكمية</th><th>سعر الوحدة</th><th>الإجمالي</th></tr></thead>
                <tbody>
                    @foreach($pr->items as $it)
                        <tr>
                            <td class="cell-title">{{ $it->displayName() }}@if(!$it->spare_part_id)<span class="badge badge-amber" style="font-size:10px">خارج الكتالوج</span>@endif</td>
                            <td>{{ $it->quantity }}</td>
                            <td>{{ $it->unit_price ? number_format($it->unit_price, 2) : '—' }}</td>
                            <td class="fw-700">{{ $it->unit_price ? number_format($it->unit_price * $it->quantity, 2) : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
        </div>

        @if($pr->rejected_reason)
            <div class="alert alert-danger mb-4"><i class="fa-solid fa-ban"></i><div><strong>سبب الرفض:</strong> {{ $pr->rejected_reason }}</div></div>
        @endif

        {{-- Approval chain --}}
        <div class="card card-body">
            <h3 class="fw-700 mb-4"><i class="fa-solid fa-route text-muted"></i> سلسلة الاعتماد</h3>
            <div class="timeline">
                <div class="timeline-item"><div class="timeline-dot bg-gray"><i class="fa-solid fa-file-circle-plus"></i></div><div class="timeline-content"><div class="timeline-title">إنشاء الطلب</div><div class="timeline-meta">{{ $pr->requester?->name }} · {{ $pr->created_at->format('Y-m-d H:i') }}</div></div></div>
                @foreach($pr->approvals as $a)
                    <div class="timeline-item">
                        <div class="timeline-dot bg-{{ $a->decision==='rejected' ? 'red' : 'green' }}"><i class="fa-solid {{ $a->decision==='rejected' ? 'fa-xmark' : 'fa-check' }}"></i></div>
                        <div class="timeline-content">
                            <div class="timeline-title">{{ $a->stageLabel() }} — {{ $a->decisionLabel() }}</div>
                            <div class="timeline-meta">{{ $a->approver?->name }} · {{ $a->decided_at?->format('Y-m-d H:i') }}</div>
                            @if($a->note)<div class="timeline-note">{{ $a->note }}</div>@endif
                        </div>
                    </div>
                @endforeach
                @if($pr->isOpen() && $pr->status !== \App\Models\PurchaseRequest::STATUS_DRAFT)
                    <div class="timeline-item"><div class="timeline-dot bg-amber"><i class="fa-solid fa-hourglass-half"></i></div><div class="timeline-content"><div class="timeline-title">المرحلة الحالية: {{ $pr->statusLabel() }}</div>@if($pr->current_dept_id)<div class="timeline-meta">بانتظار اعتماد: {{ $pr->currentDept?->name }}</div>@endif</div></div>
                @endif
            </div>
        </div>
    </div>

    <div class="card card-body">
        <div class="kv"><span class="k">القسم</span><span class="v">{{ $pr->department?->name ?? '—' }}</span></div>
        <div class="kv"><span class="k">مُقدّم الطلب</span><span class="v">{{ $pr->requester?->name ?? '—' }}</span></div>
        <div class="kv"><span class="k">النوع</span><span class="v">{{ $pr->typeLabel() }}</span></div>
        <div class="kv"><span class="k">المورّد</span><span class="v">{{ $pr->supplier ?? '—' }}</span></div>
        @if($pr->ticket)<div class="kv"><span class="k">التذكرة</span><span class="v"><a href="{{ route('tickets.show', $pr->ticket) }}" style="color:var(--primary)">{{ $pr->ticket->ticket_number }}</a></span></div>@endif
        @if($pr->justification)<div class="kv" style="flex-direction:column;align-items:flex-start;gap:6px"><span class="k">المبرّر</span><span>{{ $pr->justification }}</span></div>@endif
    </div>
</div>

@can('decide', $pr)
@push('modals')
<div class="modal-overlay" id="rejPr"><div class="modal"><form action="{{ route('purchase-requests.reject', $pr) }}" method="POST">@csrf
    <div class="modal-head"><h3>رفض الطلب</h3><span class="close" onclick="closeModal('rejPr')"><i class="fa-solid fa-xmark"></i></span></div>
    <div class="modal-body"><div class="form-group full"><label class="form-label">سبب الرفض <span class="req">*</span></label><textarea name="reason" class="form-control" rows="3" required></textarea></div></div>
    <div class="modal-foot"><button class="btn btn-danger">تأكيد الرفض</button><button type="button" class="btn btn-light" onclick="closeModal('rejPr')">إلغاء</button></div>
</form></div></div>
@endpush
@endcan
@endsection
