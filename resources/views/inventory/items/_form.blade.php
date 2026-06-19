@php($it = $item ?? null)
<div class="form-grid">
    <div class="form-group"><label class="form-label">اسم الصنف <span class="req">*</span></label><input type="text" name="name" class="form-control" value="{{ old('name', $it->name ?? '') }}" required></div>
    <div class="form-group"><label class="form-label">الرمز</label><input type="text" name="code" class="form-control" value="{{ old('code', $it->code ?? '') }}"></div>
    <div class="form-group"><label class="form-label">الفئة</label><select name="category_id" class="form-select"><option value="">—</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id', $it->category_id ?? '')==$c->id)>{{ $c->name }}</option>@endforeach</select></div>
    <div class="form-group"><label class="form-label">الكمية</label><input type="number" name="quantity" class="form-control" value="{{ old('quantity', $it->quantity ?? 0) }}"></div>
    <div class="form-group"><label class="form-label">الوحدة</label><input type="text" name="unit" class="form-control" value="{{ old('unit', $it->unit ?? '') }}"></div>
    <div class="form-group"><label class="form-label">الموقع</label><input type="text" name="location" class="form-control" value="{{ old('location', $it->location ?? '') }}"></div>
    <div class="form-group"><label class="form-label">السعر</label><input type="number" step="0.01" name="price" class="form-control" value="{{ old('price', $it->price ?? 0) }}"></div>
</div>
