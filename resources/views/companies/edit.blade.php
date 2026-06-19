@extends('layouts.app')
@section('title', 'تعديل الشركة')
@section('page-title', 'تعديل الشركة')

@section('content')
<div class="breadcrumb"><a href="{{ route('companies.index') }}">الشركات</a> <i class="fa-solid fa-chevron-left"></i> <span>{{ $company->name }}</span></div>
<form action="{{ route('companies.update', $company) }}" method="POST" class="card card-body" style="max-width:760px">
    @csrf @method('PUT')
    @include('companies._form')
    <div class="flex gap-2 mt-5"><button class="btn btn-primary"><i class="fa-solid fa-save"></i> حفظ التعديلات</button><a href="{{ route('companies.index') }}" class="btn btn-light">إلغاء</a></div>
</form>
@endsection
