@php($c = $company ?? null)
<div class="form-grid">
    <div class="form-group"><label class="form-label">اسم الشركة <span class="req">*</span></label><input type="text" name="name" class="form-control" value="{{ old('name', $c->name ?? '') }}" required></div>
    <div class="form-group"><label class="form-label">الرمز</label><input type="text" name="code" class="form-control" value="{{ old('code', $c->code ?? '') }}"></div>
    <div class="form-group"><label class="form-label">البريد الإلكتروني</label><input type="email" name="email" class="form-control" value="{{ old('email', $c->email ?? '') }}"></div>
    <div class="form-group"><label class="form-label">الهاتف</label><input type="text" name="phone" class="form-control" value="{{ old('phone', $c->phone ?? '') }}"></div>
    <div class="form-group full"><label class="form-label">العنوان</label><input type="text" name="address" class="form-control" value="{{ old('address', $c->address ?? '') }}"></div>
    <div class="form-group"><label class="form-label">الحالة</label><label class="switch" style="height:44px"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $c->is_active ?? true))><span class="track"></span><span class="text-sm">شركة نشطة</span></label></div>
</div>
