@extends('layouts.app')
@section('title', 'تعديل الموقع')
@section('page-title', 'تعديل الموقع')

@section('content')
<div class="breadcrumb"><a href="{{ route('locations.index') }}">المواقع</a> <i class="fa-solid fa-chevron-left"></i> <span>{{ $location->name }}</span></div>
<form action="{{ route('locations.update', $location) }}" method="POST" class="card card-body" style="max-width:680px">
    @csrf @method('PUT')
    @include('locations._form')
    <div class="flex gap-2 mt-5"><button class="btn btn-primary"><i class="fa-solid fa-save"></i> حفظ التعديلات</button><a href="{{ route('locations.index') }}" class="btn btn-light">إلغاء</a></div>
</form>
@endsection
