@php($current = $user ?? null)
<div class="form-grid">
    <div class="form-group">
        <label class="form-label">الاسم <span class="req">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $current->name ?? '') }}" required>
    </div>
    <div class="form-group">
        <label class="form-label">البريد الإلكتروني <span class="req">*</span></label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $current->email ?? '') }}" required>
    </div>
    <div class="form-group">
        <label class="form-label">الدور <span class="req">*</span></label>
        <select name="role" class="form-select" required>
            @foreach($roles as $r)<option value="{{ $r->name }}" @selected(old('role', $current?->roles->first()?->name) == $r->name)>{{ \App\Support\Roles::label($r->name) }}</option>@endforeach
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">القسم</label>
        <select name="department_id" class="form-select">
            <option value="">— بدون —</option>
            @foreach($departments as $d)<option value="{{ $d->id }}" @selected(old('department_id', $current->department_id ?? '') == $d->id)>{{ $d->name }}</option>@endforeach
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">الموقع</label>
        <select name="location_id" class="form-select">
            <option value="">— بدون —</option>
            @foreach($locations as $l)<option value="{{ $l->id }}" @selected(old('location_id', $current->location_id ?? '') == $l->id)>{{ $l->full_path ?: $l->name }}</option>@endforeach
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">الهاتف</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone', $current->phone ?? '') }}">
    </div>
    <div class="form-group">
        <label class="form-label">المسمى الوظيفي</label>
        <input type="text" name="job_title" class="form-control" value="{{ old('job_title', $current->job_title ?? '') }}">
    </div>
    <div class="form-group">
        <label class="form-label">كلمة المرور {!! $current ? '<span class="form-hint">(اتركها فارغة للإبقاء)</span>' : '<span class="req">*</span>' !!}</label>
        <input type="password" name="password" class="form-control" {{ $current ? '' : 'required' }}>
    </div>
    <div class="form-group">
        <label class="form-label">تأكيد كلمة المرور</label>
        <input type="password" name="password_confirmation" class="form-control">
    </div>
    <div class="form-group">
        <label class="form-label">الحالة</label>
        <label class="switch" style="height:44px"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $current->is_active ?? true))><span class="track"></span><span class="text-sm">حساب نشط</span></label>
    </div>
</div>
