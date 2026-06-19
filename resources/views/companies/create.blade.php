@extends('layouts.app')
@section('title', 'شركة جديدة')
@section('page-title', 'شركة جديدة')

@section('content')
<div class="breadcrumb"><a href="{{ route('companies.index') }}">الشركات</a> <i class="fa-solid fa-chevron-left"></i> <span>جديدة</span></div>
<form action="{{ route('companies.store') }}" method="POST" class="card card-body" style="max-width:760px">
    @csrf
    @include('companies._form', ['company' => null])

    <hr class="divider">
    <h3 class="fw-700 mb-1"><i class="fa-solid fa-user-shield text-muted"></i> مدير النظام للشركة</h3>
    <p class="text-sm text-muted mb-3">يُنشأ تلقائياً حساب "مدير النظام" (company_admin) لهذه الشركة. سيتمكّن من إدارة شركته بالكامل بشكل مستقل.</p>
    <div class="form-grid">
        <div class="form-group"><label class="form-label">اسم المدير <span class="req">*</span></label><input type="text" name="admin_name" class="form-control" value="{{ old('admin_name') }}" required></div>
        <div class="form-group"><label class="form-label">بريد الدخول <span class="req">*</span></label><input type="email" name="admin_email" class="form-control" value="{{ old('admin_email') }}" required></div>
        <div class="form-group"><label class="form-label">كلمة المرور <span class="req">*</span></label><input type="password" name="admin_password" class="form-control" required minlength="8"><span class="form-hint">8 أحرف على الأقل، تشمل حروفًا وأرقامًا.</span></div>
        <div class="form-group"><label class="form-label">تأكيد كلمة المرور <span class="req">*</span></label><input type="password" name="admin_password_confirmation" class="form-control" required minlength="8"></div>
    </div>

    <div class="flex gap-2 mt-5"><button class="btn btn-primary"><i class="fa-solid fa-save"></i> إنشاء الشركة والمدير</button><a href="{{ route('companies.index') }}" class="btn btn-light">إلغاء</a></div>
</form>
@endsection
