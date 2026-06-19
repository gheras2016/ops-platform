@extends('layouts.app')
@section('title', 'تعديل الفئة')
@section('page-title', 'تعديل الفئة')
@section('content')
<div class="breadcrumb"><a href="{{ route('inventory.categories.index') }}">الفئات</a> <i class="fa-solid fa-chevron-left"></i> <span>{{ $category->name }}</span></div>
<form action="{{ route('inventory.categories.update', $category) }}" method="POST" class="card card-body" style="max-width:680px">@csrf @method('PUT')
    @include('inventory.categories._form')
    <div class="flex gap-2 mt-5"><button class="btn btn-primary"><i class="fa-solid fa-save"></i> حفظ</button><a href="{{ route('inventory.categories.index') }}" class="btn btn-light">إلغاء</a></div>
</form>
@endsection
