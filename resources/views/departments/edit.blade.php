@extends('layouts.app')
@section('title', 'تعديل القسم')
@section('page-title', 'تعديل القسم')

@section('content')
<div class="breadcrumb"><a href="{{ route('departments.index') }}">الأقسام</a> <i class="fa-solid fa-chevron-left"></i> <span>{{ $department->name }}</span></div>
<form action="{{ route('departments.update', $department) }}" method="POST" class="card card-body" style="max-width:760px">
    @csrf @method('PUT')
    @include('departments._form')
    <div class="flex gap-2 mt-5"><button class="btn btn-primary"><i class="fa-solid fa-save"></i> حفظ التعديلات</button><a href="{{ route('departments.index') }}" class="btn btn-light">إلغاء</a></div>
</form>

<form action="{{ route('departments.destroy', $department) }}" method="POST" class="mt-4" onsubmit="return confirm('حذف القسم؟')" style="max-width:760px">
    @csrf @method('DELETE')
    <button class="btn btn-ghost" style="color:var(--danger)"><i class="fa-solid fa-trash"></i> حذف القسم</button>
</form>
@endsection
