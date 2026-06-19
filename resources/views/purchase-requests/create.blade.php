@extends('layouts.app')
@section('title', 'طلب شراء جديد')
@section('page-title', 'طلب شراء جديد')

@section('content')
<div class="breadcrumb"><a href="{{ route('purchase-requests.index') }}">طلبات الشراء</a> <i class="fa-solid fa-chevron-left"></i> <span>جديد</span></div>

<form action="{{ route('purchase-requests.store') }}" method="POST" class="card card-body" style="max-width:880px">
    @csrf
    <div class="form-grid">
        <div class="form-group">
            <label class="form-label">القسم <span class="req">*</span></label>
            <select name="department_id" class="form-select" required>
                @foreach($departments as $d)<option value="{{ $d->id }}" @selected(old('department_id')==$d->id)>{{ $d->name }}</option>@endforeach
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">نوع الشراء <span class="req">*</span></label>
            <select name="fulfillment_type" class="form-select" required>
                @foreach($types as $v => $label)<option value="{{ $v }}" @selected(old('fulfillment_type')==$v)>{{ $label }}</option>@endforeach
            </select>
            <span class="form-hint">«توريد للمخزون» يزيد الرصيد، و«شراء مباشر عاجل» يُحمّل على التذكرة دون دخول المستودع.</span>
        </div>
        <div class="form-group">
            <label class="form-label">المورّد المقترح</label>
            <input type="text" name="supplier" class="form-control" value="{{ old('supplier') }}">
        </div>
        <div class="form-group">
            <label class="form-label">ربط بتذكرة (اختياري)</label>
            <select name="ticket_id" class="form-select">
                <option value="">— بدون —</option>
                @foreach($tickets as $t)<option value="{{ $t->id }}" @selected(old('ticket_id')==$t->id)>{{ $t->ticket_number }} — {{ \Illuminate\Support\Str::limit($t->title, 40) }}</option>@endforeach
            </select>
        </div>
        <div class="form-group full">
            <label class="form-label">مبرّر الطلب</label>
            <textarea name="justification" class="form-control" rows="2" placeholder="سبب الحاجة للشراء...">{{ old('justification') }}</textarea>
        </div>
    </div>

    <hr class="divider">
    <label class="form-label mb-2">الأصناف المطلوبة <span class="req">*</span></label>
    <div id="prItems" class="flex" style="flex-direction:column;gap:10px"></div>
    <div class="flex gap-2 mt-2">
        <button type="button" class="btn btn-light btn-sm" onclick="addCatalog()"><i class="fa-solid fa-plus"></i> صنف من الكتالوج</button>
        <button type="button" class="btn btn-light btn-sm" onclick="addCustom()"><i class="fa-solid fa-pen"></i> صنف غير موجود</button>
    </div>

    <div class="flex gap-2 mt-5">
        <button class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> إرسال للاعتماد</button>
        <a href="{{ route('purchase-requests.index') }}" class="btn btn-light">إلغاء</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
const parts = @json($spareParts->map(fn($p)=>['id'=>$p->id,'label'=>$p->name.' ('.$p->part_number.') — '.($p->category?->name ?? 'عام'),'price'=>$p->unit_price]));
let idx = 0;
function rowShell(inner){ const r=document.createElement('div'); r.className='flex gap-2 items-center'; r.innerHTML=inner; document.getElementById('prItems').appendChild(r); idx++; }
function addCatalog(){
    const opts = parts.map(p=>`<option value="${p.id}" data-price="${p.price??''}">${p.label}</option>`).join('');
    rowShell(`<select name="items[${idx}][spare_part_id]" class="form-select" style="flex:1" onchange="let p=this.options[this.selectedIndex].dataset.price; if(p) this.parentElement.querySelector('.pu').value=p;"><option value="">— اختر —</option>${opts}</select>
        <input type="number" name="items[${idx}][quantity]" class="form-control" style="width:80px" min="1" value="1" placeholder="الكمية">
        <input type="number" step="0.01" name="items[${idx}][unit_price]" class="form-control pu" style="width:110px" placeholder="سعر تقديري">
        <button type="button" class="btn btn-ghost btn-icon" style="color:var(--danger)" onclick="this.parentElement.remove()"><i class="fa-solid fa-trash"></i></button>`);
}
function addCustom(){
    rowShell(`<input type="text" name="items[${idx}][custom_name]" class="form-control" style="flex:1" placeholder="اسم الصنف غير الموجود..." required>
        <input type="number" name="items[${idx}][quantity]" class="form-control" style="width:80px" min="1" value="1" placeholder="الكمية">
        <input type="number" step="0.01" name="items[${idx}][unit_price]" class="form-control" style="width:110px" placeholder="سعر تقديري">
        <span class="badge badge-amber">جديد</span>
        <button type="button" class="btn btn-ghost btn-icon" style="color:var(--danger)" onclick="this.parentElement.remove()"><i class="fa-solid fa-trash"></i></button>`);
}
addCatalog();
</script>
@endpush
