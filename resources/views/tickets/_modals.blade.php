@php($user = auth()->user())

{{-- Assign modal (department head) --}}
@if($user->can('assign', $ticket) && $ticket->canBeAssigned())
@push('modals')
<div class="modal-overlay" id="assignModal">
    <div class="modal">
        <form action="{{ route('tickets.assign', $ticket) }}" method="POST">
            @csrf
            <div class="modal-head"><h3>إسناد التذكرة لفني</h3><span class="close" onclick="closeModal('assignModal')"><i class="fa-solid fa-xmark"></i></span></div>
            <div class="modal-body">
                <div class="form-group full mb-4">
                    <label class="form-label">اختر الفني <span class="req">*</span></label>
                    <select name="technician_id" class="form-select" required>
                        <option value="">— اختر فنيًا من القسم —</option>
                        @foreach($technicians as $tech)
                            <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                        @endforeach
                    </select>
                    @if($technicians->isEmpty())<div class="form-error mt-2">لا يوجد فنيون في هذا القسم. أضف فنيًا أولاً.</div>@endif
                </div>
                <div class="form-grid mb-4">
                    <div class="form-group">
                        <label class="form-label">الأولوية</label>
                        <select name="priority_id" class="form-select">
                            @foreach($priorities as $p)
                                <option value="{{ $p->id }}" @selected($ticket->priority_id == $p->id)>{{ $p->name }}</option>
                            @endforeach
                        </select>
                        <span class="form-hint">يحدّدها رئيس القسم بحسب أهمية العطل.</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">الموعد المتوقع للإنجاز</label>
                        <input type="date" name="due_at" class="form-control" value="{{ $ticket->due_at?->format('Y-m-d') }}">
                    </div>
                </div>
                <div class="form-group full">
                    <label class="form-label">ملاحظة للفني</label>
                    <textarea name="note" class="form-control" rows="2" placeholder="تعليمات أو ملاحظات..."></textarea>
                </div>
            </div>
            <div class="modal-foot">
                <button class="btn btn-primary"><i class="fa-solid fa-user-check"></i> إسناد</button>
                <button type="button" class="btn btn-light" onclick="closeModal('assignModal')">إلغاء</button>
            </div>
        </form>
    </div>
</div>
@endpush
@endif

{{-- Pause modal (technician) --}}
@if($user->can('work', $ticket) && $ticket->canBePaused())
@push('modals')
<div class="modal-overlay" id="pauseModal">
    <div class="modal">
        <form action="{{ route('tickets.pause', $ticket) }}" method="POST">
            @csrf
            <div class="modal-head"><h3>إيقاف العمل مؤقتًا</h3><span class="close" onclick="closeModal('pauseModal')"><i class="fa-solid fa-xmark"></i></span></div>
            <div class="modal-body">
                <div class="form-group full mb-4">
                    <label class="form-label">سبب الإيقاف <span class="req">*</span></label>
                    <select name="reason_code" class="form-select" required>
                        @foreach(\App\Models\TicketPauseLog::REASONS as $code => $label)
                            <option value="{{ $code }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group full">
                    <label class="form-label">تفاصيل إضافية</label>
                    <textarea name="reason" class="form-control" rows="2" placeholder="مثال: في انتظار وصول فلتر المكيف من المورد..."></textarea>
                </div>
            </div>
            <div class="modal-foot">
                <button class="btn btn-warning"><i class="fa-solid fa-pause"></i> إيقاف مؤقت</button>
                <button type="button" class="btn btn-light" onclick="closeModal('pauseModal')">إلغاء</button>
            </div>
        </form>
    </div>
</div>
@endpush
@endif

{{-- Resolve modal (technician) --}}
@if($user->can('work', $ticket) && $ticket->canBeResolved())
@push('modals')
<div class="modal-overlay" id="resolveModal">
    <div class="modal lg">
        <form action="{{ route('tickets.resolve', $ticket) }}" method="POST">
            @csrf
            <div class="modal-head"><h3>إنهاء وتسليم التذكرة</h3><span class="close" onclick="closeModal('resolveModal')"><i class="fa-solid fa-xmark"></i></span></div>
            <div class="modal-body">
                <p class="text-sm text-muted mb-4">سيتم رفع التذكرة لرئيس القسم لاعتماد الإنجاز.</p>
                <div class="form-group full mb-4">
                    <label class="form-label">ملخص ما تم إنجازه</label>
                    <textarea name="resolution_note" class="form-control" rows="3" placeholder="صف الحل الذي تم تنفيذه..."></textarea>
                </div>

                <div class="form-group full">
                    <label class="form-label">قطع الغيار المستخدمة <span class="form-hint">(من المخزون: تُخصم تلقائيًا · خارج الكاتالوج: تُسجَّل بالاسم فقط)</span></label>
                    <div id="partsRows" class="flex" style="flex-direction:column;gap:10px"></div>
                    <div class="flex gap-2 mt-2">
                        <button type="button" class="btn btn-light btn-sm" onclick="addPartRow()"><i class="fa-solid fa-plus"></i> قطعة من المخزون</button>
                        <button type="button" class="btn btn-light btn-sm" onclick="addCustomPartRow()"><i class="fa-solid fa-pen"></i> قطعة خارج الكاتالوج</button>
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button class="btn btn-success"><i class="fa-solid fa-flag-checkered"></i> تأكيد الإنجاز</button>
                <button type="button" class="btn btn-light" onclick="closeModal('resolveModal')">إلغاء</button>
            </div>
        </form>
    </div>
</div>
@push('scripts')
<script>
const sparePartsList = @json($spareParts->map(fn($p)=>['id'=>$p->id,'label'=>$p->name.' ('.$p->part_number.') — متاح: '.$p->quantity]));
let partRowIndex = 0;
function addPartRow(){
    const wrap = document.getElementById('partsRows');
    const row = document.createElement('div');
    row.className = 'flex gap-2 items-center';
    const opts = sparePartsList.map(p => `<option value="${p.id}">${p.label}</option>`).join('');
    row.innerHTML = `
        <select name="parts[${partRowIndex}][spare_part_id]" class="form-select" style="flex:1"><option value="">— اختر قطعة —</option>${opts}</select>
        <input type="number" name="parts[${partRowIndex}][quantity]" class="form-control" style="width:90px" min="1" value="1" placeholder="الكمية">
        <button type="button" class="btn btn-ghost btn-icon" style="color:var(--danger)" onclick="this.parentElement.remove()"><i class="fa-solid fa-trash"></i></button>`;
    wrap.appendChild(row);
    partRowIndex++;
}
function addCustomPartRow(){
    const wrap = document.getElementById('partsRows');
    const row = document.createElement('div');
    row.className = 'flex gap-2 items-center';
    row.innerHTML = `
        <span class="badge badge-amber" style="white-space:nowrap"><i class="fa-solid fa-pen"></i> خارج الكاتالوج</span>
        <input type="text" name="parts[${partRowIndex}][custom_name]" class="form-control" style="flex:1" placeholder="اسم القطعة المستخدمة" required>
        <input type="number" name="parts[${partRowIndex}][quantity]" class="form-control" style="width:80px" min="1" value="1" placeholder="الكمية">
        <input type="number" name="parts[${partRowIndex}][unit_cost]" class="form-control" style="width:110px" min="0" step="0.01" placeholder="التكلفة (اختياري)">
        <button type="button" class="btn btn-ghost btn-icon" style="color:var(--danger)" onclick="this.parentElement.remove()"><i class="fa-solid fa-trash"></i></button>`;
    wrap.appendChild(row);
    partRowIndex++;
}
</script>
@endpush
@endpush
@endif

{{-- Approve / Reject modals (department head) --}}
@if($user->can('approve', $ticket) && $ticket->canBeApproved())
@push('modals')
<div class="modal-overlay" id="approveModal">
    <div class="modal">
        <form action="{{ route('tickets.approve', $ticket) }}" method="POST">
            @csrf
            <div class="modal-head"><h3>اعتماد الإنجاز وإغلاق التذكرة</h3><span class="close" onclick="closeModal('approveModal')"><i class="fa-solid fa-xmark"></i></span></div>
            <div class="modal-body">
                <p class="text-sm text-muted mb-4">تأكيد إغلاق التذكرة بعد التحقق من جودة الإنجاز.</p>
                <div class="form-group full">
                    <label class="form-label">ملاحظة الاعتماد</label>
                    <textarea name="note" class="form-control" rows="2" placeholder="اختياري"></textarea>
                </div>
            </div>
            <div class="modal-foot">
                <button class="btn btn-success"><i class="fa-solid fa-circle-check"></i> اعتماد وإغلاق</button>
                <button type="button" class="btn btn-light" onclick="closeModal('approveModal')">إلغاء</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="rejectModal">
    <div class="modal">
        <form action="{{ route('tickets.reject', $ticket) }}" method="POST">
            @csrf
            <div class="modal-head"><h3>رفض وإعادة للفني</h3><span class="close" onclick="closeModal('rejectModal')"><i class="fa-solid fa-xmark"></i></span></div>
            <div class="modal-body">
                <div class="form-group full">
                    <label class="form-label">سبب الإعادة <span class="req">*</span></label>
                    <textarea name="reason" class="form-control" rows="3" placeholder="وضّح ما يجب تصحيحه..." required></textarea>
                </div>
            </div>
            <div class="modal-foot">
                <button class="btn btn-danger"><i class="fa-solid fa-rotate-left"></i> رفض وإعادة</button>
                <button type="button" class="btn btn-light" onclick="closeModal('rejectModal')">إلغاء</button>
            </div>
        </form>
    </div>
</div>
@endpush
@endif
