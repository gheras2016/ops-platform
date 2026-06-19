@extends('layouts.app')
@section('title', 'قسم جديد')
@section('page-title', 'قسم جديد')

@section('content')
<div class="breadcrumb"><a href="{{ route('departments.index') }}">الأقسام</a> <i class="fa-solid fa-chevron-left"></i> <span>جديد</span></div>
<form action="{{ route('departments.store') }}" method="POST" class="card card-body" style="max-width:760px">
    @csrf
    @include('departments._form', ['department' => null])
    <div class="flex gap-2 mt-5"><button class="btn btn-primary"><i class="fa-solid fa-save"></i> حفظ</button><a href="{{ route('departments.index') }}" class="btn btn-light">إلغاء</a></div>
</form>
@endsection
