@php($c = $category ?? null)
<div class="form-grid">
    <div class="form-group">
        <label class="form-label">اسم التصنيف <span class="req">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $c->name ?? '') }}" required>
    </div>
    <div class="form-group">
        <label class="form-label">الرمز</label>
        <input type="text" name="code" class="form-control" value="{{ old('code', $c->code ?? '') }}">
    </div>
    <div class="form-group full">
        <label class="form-label">القسم</label>
        <select name="department_id" class="form-select">
            <option value="">— عام (يظهر لكل الأقسام) —</option>
            @foreach($departments as $d)
                <option value="{{ $d->id }}" @selected(old('department_id', $c->department_id ?? '') == $d->id)>{{ $d->name }}</option>
            @endforeach
        </select>
        <span class="form-hint">اربط التصنيف بقسم ليظهر لفنيي ذلك القسم فقط، أو اتركه "عام" ليظهر للجميع.</span>
    </div>
</div>
