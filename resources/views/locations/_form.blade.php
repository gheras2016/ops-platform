@php($loc = $location ?? null)
<div class="form-grid">
    <div class="form-group">
        <label class="form-label">اسم الموقع <span class="req">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $loc->name ?? '') }}" placeholder="مثال: المبنى الرئيسي" required>
    </div>
    <div class="form-group">
        <label class="form-label">النوع <span class="req">*</span></label>
        <select name="type" class="form-select" required>
            @foreach($types as $value => $label)
                <option value="{{ $value }}" @selected(old('type', $loc->type ?? 'building') == $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group full">
        <label class="form-label">يتبع لموقع (اختياري)</label>
        <div class="flex gap-2">
            <select name="parent_id" id="parentSelect" class="form-select" style="flex:1">
                <option value="">— موقع رئيسي —</option>
                @foreach($parents as $p)
                    <option value="{{ $p->id }}" @selected(old('parent_id', $loc->parent_id ?? '') == $p->id)>{{ $p->full_path ?: $p->name }}</option>
                @endforeach
            </select>
            <button type="button" class="btn btn-outline" onclick="openModal('quickLocModal')" title="إضافة موقع جديد"><i class="fa-solid fa-plus"></i> جديد</button>
        </div>
        <span class="form-hint">اختر المبنى أو الدور الذي يندرج تحته هذا الموقع، أو أضف موقعًا جديدًا بزر "جديد".</span>
    </div>
</div>

@include('partials.location-quick-add', ['targetSelect' => 'parentSelect', 'locationOptions' => $parents])
