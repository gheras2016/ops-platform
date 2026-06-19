@extends('layouts.app')
@section('title', 'تعديل التصنيف')
@section('page-title', 'تعديل تصنيف الإسبير')
@section('content')
<div class="breadcrumb"><a href="{{ route('spare-categories.index') }}">تصنيفات الإسبير</a> <i class="fa-solid fa-chevron-left"></i> <span>{{ $category->name }}</span></div>
<form action="{{ route('spare-categories.update', $category) }}" method="POST" class="card card-body" style="max-width:680px">@csrf @method('PUT')
    @include('spare-parts.categories._form')
    <div class="flex gap-2 mt-5"><button class="btn btn-primary"><i class="fa-solid fa-save"></i> حفظ</button><a href="{{ route('spare-categories.index') }}" class="btn btn-light">إلغاء</a></div>
</form>
@endsection
