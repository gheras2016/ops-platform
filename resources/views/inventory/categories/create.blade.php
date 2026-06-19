@extends('layouts.app')
@section('title', 'فئة جديدة')
@section('page-title', 'فئة جديدة')
@section('content')
<div class="breadcrumb"><a href="{{ route('inventory.categories.index') }}">الفئات</a> <i class="fa-solid fa-chevron-left"></i> <span>جديدة</span></div>
<form action="{{ route('inventory.categories.store') }}" method="POST" class="card card-body" style="max-width:680px">@csrf
    @include('inventory.categories._form', ['category' => null])
    <div class="flex gap-2 mt-5"><button class="btn btn-primary"><i class="fa-solid fa-save"></i> حفظ</button><a href="{{ route('inventory.categories.index') }}" class="btn btn-light">إلغاء</a></div>
</form>
@endsection
