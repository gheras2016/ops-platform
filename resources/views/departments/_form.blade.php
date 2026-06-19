<div class="form-grid">
    <div class="form-group">
        <label class="form-label">اسم القسم <span class="req">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $department->name ?? '') }}" required>
    </div>
    <div class="form-group">
        <label class="form-label">الرمز</label>
        <input type="text" name="code" class="form-control" value="{{ old('code', $department->code ?? '') }}" placeholder="IT, MTN...">
    </div>
    <div class="form-group">
        <label class="form-label">نوع المهام <span class="req">*</span></label>
        <select name="type" class="form-select" required>
            @foreach($types as $value => $label)
                <option value="{{ $value }}" @selected(old('type', $department->type ?? 'general') == $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">يتبع إداريًا لـ (الإدارة الأعلى)</label>
        <select name="parent_id" class="form-select">
            <option value="">— لا يتبع (قسم رئيسي) —</option>
            @foreach(($parents ?? []) as $p)
                <option value="{{ $p->id }}" @selected(old('parent_id', $department->parent_id ?? '') == $p->id)>{{ $p->name }}</option>
            @endforeach
        </select>
        <span class="form-hint">يحدّد تصاعد اعتماد طلبات الشراء.</span>
    </div>
    <div class="form-group">
        <label class="form-label">رئيس القسم</label>
        <select name="head_id" class="form-select">
            <option value="">— غير محدد —</option>
            @foreach($heads as $h)<option value="{{ $h->id }}" @selected(old('head_id', $department->head_id ?? '') == $h->id)>{{ $h->name }}</option>@endforeach
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">اللون</label>
        <select name="color" class="form-select">
            @foreach(['indigo'=>'بنفسجي','blue'=>'أزرق','teal'=>'أخضر مزرق','amber'=>'كهرماني','orange'=>'برتقالي','red'=>'أحمر','slate'=>'رمادي'] as $v=>$l)
                <option value="{{ $v }}" @selected(old('color', $department->color ?? 'slate') == $v)>{{ $l }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">الحالة</label>
        <label class="switch" style="height:44px"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $department->is_active ?? true))><span class="track"></span><span class="text-sm">قسم نشط</span></label>
    </div>
    <div class="form-group">
        <label class="form-label">استقبال البلاغات</label>
        <label class="switch" style="height:44px"><input type="checkbox" name="accepts_tickets" value="1" @checked(old('accepts_tickets', $department->accepts_tickets ?? true))><span class="track"></span><span class="text-sm">يستقبل بلاغات الصيانة</span></label>
        <span class="form-hint">أوقفه للإدارات الإشرافية (مثل إدارة التشغيل) التي تعتمد فقط ولا تستقبل بلاغات.</span>
    </div>
    <div class="form-group full">
        <label class="form-label">الوصف</label>
        <textarea name="description" class="form-control" rows="2">{{ old('description', $department->description ?? '') }}</textarea>
    </div>
</div>
