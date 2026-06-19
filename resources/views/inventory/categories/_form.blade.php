@php($c = $category ?? null)
<div class="form-grid">
    <div class="form-group"><label class="form-label">اسم الفئة <span class="req">*</span></label><input type="text" name="name" class="form-control" value="{{ old('name', $c->name ?? '') }}" required></div>
    <div class="form-group"><label class="form-label">الرمز</label><input type="text" name="code" class="form-control" value="{{ old('code', $c->code ?? '') }}"></div>
    <div class="form-group full"><label class="form-label">الوصف</label><textarea name="description" class="form-control" rows="2">{{ old('description', $c->description ?? '') }}</textarea></div>
</div>
