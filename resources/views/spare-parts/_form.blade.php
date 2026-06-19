@php($p = $sparePart ?? null)
<div class="form-grid">
    <div class="form-group"><label class="form-label">اسم القطعة <span class="req">*</span></label><input type="text" name="name" class="form-control" value="{{ old('name', $p->name ?? '') }}" required></div>
    <div class="form-group"><label class="form-label">رقم القطعة <span class="req">*</span></label><input type="text" name="part_number" class="form-control" value="{{ old('part_number', $p->part_number ?? '') }}" required></div>
    <div class="form-group"><label class="form-label">الفئة</label><select name="category_id" class="form-select"><option value="">—</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id', $p->category_id ?? '')==$c->id)>{{ $c->name }}</option>@endforeach</select></div>
    <div class="form-group"><label class="form-label">الكمية</label><input type="number" name="quantity" class="form-control" value="{{ old('quantity', $p->quantity ?? 0) }}"></div>
    <div class="form-group"><label class="form-label">الحد الأدنى</label><input type="number" name="min_stock" class="form-control" value="{{ old('min_stock', $p->min_stock ?? 0) }}"></div>
    <div class="form-group"><label class="form-label">الحد الأقصى</label><input type="number" name="max_stock" class="form-control" value="{{ old('max_stock', $p->max_stock ?? '') }}"></div>
    <div class="form-group"><label class="form-label">سعر الوحدة</label><input type="number" step="0.01" name="unit_price" class="form-control" value="{{ old('unit_price', $p->unit_price ?? '') }}"></div>
</div>
