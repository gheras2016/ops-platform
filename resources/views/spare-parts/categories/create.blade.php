@extends('layouts.app')
@section('title', 'تصنيف إسبير جديد')
@section('page-title', 'تصنيف إسبير جديد')
@section('content')
<div class="breadcrumb"><a href="{{ route('spare-categories.index') }}">تصنيفات الإسبير</a> <i class="fa-solid fa-chevron-left"></i> <span>جديد</span></div>
<form action="{{ route('spare-categories.store') }}" method="POST" class="card card-body" style="max-width:680px">@csrf
    @include('spare-parts.categories._form', ['category' => null])
    <div class="flex gap-2 mt-5"><button class="btn btn-primary"><i class="fa-solid fa-save"></i> حفظ</button><a href="{{ route('spare-categories.index') }}" class="btn btn-light">إلغاء</a></div>
</form>
@endsection
