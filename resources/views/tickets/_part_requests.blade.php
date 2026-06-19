@php($user = auth()->user())
@php($canRequest = $user->can('work', $ticket) && in_array($ticket->status, [\App\Models\Ticket::STATUS_ACCEPTED, \App\Models\Ticket::STATUS_IN_PROGRESS, \App\Models\Ticket::STATUS_PAUSED]))

<div class="card mb-4">
    <div class="card-header">
        <h3><i class="fa-solid fa-dolly text-muted"></i> طلبات صرف الإسبير</h3>
        <span class="sub">{{ $ticket->partRequests->count() }} طلب</span>
        @if($canRequest && $spareParts->isNotEmpty())
            <button class="btn btn-primary btn-sm" style="margin-right:auto" onclick="openModal('reqPartsModal')"><i class="fa-solid fa-cart-plus"></i> طلب قطع غيار</button>
        @endif
    </div>
    <div class="card-body">
        @forelse($ticket->partRequests as $pr)
            <div class="card" style="margin-bottom:14px">
                <div class="card-body" style="padding:14px 16px">
                    <div class="flex items-center justify-between mb-3" style="flex-wrap:wrap;gap:8px">
                        <div>
                            <span class="fw-700">{{ $pr->request_number }}</span>
                            <span class="badge badge-{{ $pr->statusColor() }} badge-dot" style="margin-right:6px">{{ $pr->statusLabel() }}</span>
                        </div>
                        <div class="text-xs text-muted">{{ $pr->requester?->name }} · {{ $pr->created_at->diffForHumans() }}</div>
                    </div>

                    <div class="table-wrap"><table class="table" style="font-size:13px">
                        <thead><tr><th>القطعة</th><th>مطلوب</th><th>معتمد</th><th>مصروف</th><th>الحالة</th></tr></thead>
                        <tbody>
                            @foreach($pr->items as $it)
                                @php([$stLabel, $stColor, $stIcon] = $it->fulfilmentStatus())
                                <tr>
                                    <td class="cell-title">
                                        {{ $it->displayName() }}
                                        @if($it->isCustom())<span class="badge badge-amber" style="font-size:10px">خارج الكتالوج</span>
                                        @else<span class="cell-sub">({{ $it->sparePart?->part_number }})</span>@endif
                                    </td>
                                    <td>{{ $it->qty_requested }}</td>
                                    <td>{{ $it->qty_approved }}</td>
                                    <td class="fw-700">{{ $it->qty_issued }}</td>
                                    <td>
                                        <span class="badge badge-{{ $stColor }}"><i class="fa-solid {{ $stIcon }}"></i> {{ $stLabel }}</span>
                                        @if($stColor === 'orange' && $pr->purchaseRequest)
                                            <a href="{{ route('purchase-requests.show', $pr->purchaseRequest) }}" class="cell-sub" style="color:var(--primary)">({{ $pr->purchaseRequest->request_number }})</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table></div>

                    @if($pr->rejected_reason && $pr->status === \App\Models\PartRequest::STATUS_REJECTED)
                        <div class="alert alert-warning mt-3" style="margin-bottom:0"><i class="fa-solid fa-ban"></i> {{ $pr->rejected_reason }}</div>
                    @endif

                    {{-- Linked purchase (procurement) status --}}
                    @if($pr->purchaseRequest)
                        @php($po = $pr->purchaseRequest)
                        <div class="alert alert-info mt-3" style="margin-bottom:0">
                            <i class="fa-solid fa-truck"></i>
                            <div style="flex:1">طلب شراء <strong>{{ $po->request_number }}</strong> — <span class="badge badge-{{ $po->statusColor() }}">{{ $po->statusLabel() }}</span></div>
                            <a href="{{ route('purchase-requests.show', $po) }}" class="btn btn-light btn-sm">عرض الطلب</a>
                            @if($po->canBeReceived() && $user->canManageInventory())
                                <form action="{{ route('purchase-requests.receive', $po) }}" method="POST" onsubmit="return confirm('تأكيد تنفيذ الطلب؟')">@csrf<button class="btn btn-success btn-sm"><i class="fa-solid fa-box-open"></i> تنفيذ</button></form>
                            @endif
                        </div>
                    @endif

                    <div class="action-bar mt-3">
                        @if($pr->canBeApproved() && $user->can('approve', $pr))
                            <form action="{{ route('part-requests.approve', $pr) }}" method="POST">@csrf<button class="btn btn-success btn-sm"><i class="fa-solid fa-check"></i> اعتماد</button></form>
                            <button class="btn btn-danger btn-sm" onclick="openModal('rejectPr{{ $pr->id }}')"><i class="fa-solid fa-xmark"></i> رفض</button>
                        @endif
                        @if($pr->canBeIssued() && $user->can('issue', $pr))
                            <button class="btn btn-primary btn-sm" onclick="openModal('issuePr{{ $pr->id }}')"><i class="fa-solid fa-dolly"></i> صرف</button>
                            @if(! $pr->purchaseRequest)
                                <form action="{{ route('part-requests.convert', $pr) }}" method="POST" onsubmit="return confirm('تحويل النقص/القطع غير المتوفرة إلى طلب شراء؟')">@csrf<button class="btn btn-warning btn-sm"><i class="fa-solid fa-cart-arrow-down"></i> غير متوفر — تحويل لشراء</button></form>
                            @endif
                        @endif
                        @if($pr->canBeCancelled() && $user->can('cancel', $pr))
                            <form action="{{ route('part-requests.cancel', $pr) }}" method="POST" onsubmit="return confirm('إلغاء الطلب؟')">@csrf<button class="btn btn-light btn-sm">إلغاء</button></form>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Reject modal --}}
            @if($pr->canBeApproved() && $user->can('approve', $pr))
            @push('modals')
            <div class="modal-overlay" id="rejectPr{{ $pr->id }}">
                <div class="modal"><form action="{{ route('part-requests.reject', $pr) }}" method="POST">@csrf
                    <div class="modal-head"><h3>رفض طلب الإسبير {{ $pr->request_number }}</h3><span class="close" onclick="closeModal('rejectPr{{ $pr->id }}')"><i class="fa-solid fa-xmark"></i></span></div>
                    <div class="modal-body"><div class="form-group full"><label class="form-label">سبب الرفض <span class="req">*</span></label><textarea name="reason" class="form-control" rows="3" required></textarea></div></div>
                    <div class="modal-foot"><button class="btn btn-danger">تأكيد الرفض</button><button type="button" class="btn btn-light" onclick="closeModal('rejectPr{{ $pr->id }}')">إلغاء</button></div>
                </form></div>
            </div>
            @endpush
            @endif

            {{-- Issue modal (warehouse) --}}
            @if($pr->canBeIssued() && $user->can('issue', $pr))
            @push('modals')
            <div class="modal-overlay" id="issuePr{{ $pr->id }}">
                <div class="modal lg"><form action="{{ route('part-requests.issue', $pr) }}" method="POST">@csrf
                    <div class="modal-head"><h3>صرف الطلب {{ $pr->request_number }}</h3><span class="close" onclick="closeModal('issuePr{{ $pr->id }}')"><i class="fa-solid fa-xmark"></i></span></div>
                    <div class="modal-body">
                        <p class="text-sm text-muted mb-3">حدّد الكمية المصروفة لكل قطعة (المتاح = الرصيد − المحجوز).</p>
                        <div class="table-wrap"><table class="table" style="font-size:13px">
                            <thead><tr><th>القطعة</th><th>المتبقي</th><th>المتاح</th><th>صرف</th></tr></thead>
                            <tbody>
                                @foreach($pr->items as $it)
                                    @continue($it->isCustom() || $it->outstanding() <= 0)
                                    @php($avail = $it->sparePart?->availableQty() ?? 0)
                                    @php($outstanding = $it->outstanding())
                                    <tr>
                                        <td class="cell-title">{{ $it->sparePart?->name }}</td>
                                        <td>{{ $outstanding }}</td>
                                        <td class="{{ $avail < $outstanding ? 'fw-700' : '' }}" style="{{ $avail < $outstanding ? 'color:var(--danger)' : '' }}">{{ $avail }}</td>
                                        <td><input type="number" name="issue[{{ $it->id }}]" class="form-control" style="width:90px" min="0" max="{{ min($outstanding, $avail) }}" value="{{ min($outstanding, $avail) }}"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table></div>
                    </div>
                    <div class="modal-foot"><button class="btn btn-primary"><i class="fa-solid fa-dolly"></i> تأكيد الصرف</button><button type="button" class="btn btn-light" onclick="closeModal('issuePr{{ $pr->id }}')">إلغاء</button></div>
                </form></div>
            </div>
            @endpush
            @endif
        @empty
            <p class="text-muted text-sm text-center" style="padding:14px">لا توجد طلبات إسبير لهذه التذكرة.</p>
        @endforelse
    </div>
</div>

{{-- Create request modal (technician) --}}
@if($canRequest && $spareParts->isNotEmpty())
@push('modals')
<div class="modal-overlay" id="reqPartsModal">
    <div class="modal lg"><form action="{{ route('tickets.part-requests.store', $ticket) }}" method="POST">@csrf
        <div class="modal-head"><h3>طلب صرف قطع غيار</h3><span class="close" onclick="closeModal('reqPartsModal')"><i class="fa-solid fa-xmark"></i></span></div>
        <div class="modal-body">
            <p class="text-sm text-muted mb-3">القطع المعروضة تخص مجال قسمك + المستهلكات العامة. سيتم إيقاف التذكرة مؤقتًا بانتظار الصرف.</p>
            <div id="reqRows" class="flex" style="flex-direction:column;gap:10px"></div>
            <div class="flex gap-2 mt-2">
                <button type="button" class="btn btn-light btn-sm" onclick="addReqRow()"><i class="fa-solid fa-plus"></i> قطعة من القائمة</button>
                <button type="button" class="btn btn-light btn-sm" onclick="addCustomRow()"><i class="fa-solid fa-pen"></i> قطعة غير موجودة بالقائمة</button>
            </div>
            <div class="form-group full mt-4"><label class="form-label">ملاحظة</label><textarea name="note" class="form-control" rows="2" placeholder="سبب الحاجة للقطع..."></textarea></div>
        </div>
        <div class="modal-foot"><button class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> إرسال الطلب</button><button type="button" class="btn btn-light" onclick="closeModal('reqPartsModal')">إلغاء</button></div>
    </form></div>
</div>
@endpush
@push('scripts')
<script>
const reqParts = @json($spareParts->map(fn($p)=>['id'=>$p->id,'label'=>$p->name.' ('.$p->part_number.') — '.($p->category?->department?->name ?? 'عام').' — الرصيد: '.$p->quantity]));
let reqIdx = 0;
function addReqRow(){
    const wrap = document.getElementById('reqRows');
    const row = document.createElement('div');
    row.className = 'flex gap-2 items-center';
    const opts = reqParts.map(p => `<option value="${p.id}">${p.label}</option>`).join('');
    row.innerHTML = `<select name="parts[${reqIdx}][spare_part_id]" class="form-select" style="flex:1"><option value="">— اختر قطعة —</option>${opts}</select>
        <input type="number" name="parts[${reqIdx}][quantity]" class="form-control" style="width:90px" min="1" value="1">
        <button type="button" class="btn btn-ghost btn-icon" style="color:var(--danger)" onclick="this.parentElement.remove()"><i class="fa-solid fa-trash"></i></button>`;
    wrap.appendChild(row); reqIdx++;
}
function addCustomRow(){
    const wrap = document.getElementById('reqRows');
    const row = document.createElement('div');
    row.className = 'flex gap-2 items-center';
    row.innerHTML = `<input type="text" name="parts[${reqIdx}][custom_name]" class="form-control" style="flex:1" placeholder="اكتب اسم القطعة غير الموجودة بالقائمة..." required>
        <input type="number" name="parts[${reqIdx}][quantity]" class="form-control" style="width:90px" min="1" value="1">
        <span class="badge badge-amber" title="ستُحوَّل للشراء">جديدة</span>
        <button type="button" class="btn btn-ghost btn-icon" style="color:var(--danger)" onclick="this.parentElement.remove()"><i class="fa-solid fa-trash"></i></button>`;
    wrap.appendChild(row); reqIdx++;
}
addReqRow();
</script>
@endpush
@endif
