@extends('layouts.app')
@section('title', 'موقع جديد')
@section('page-title', 'موقع جديد')

@section('content')
<div class="breadcrumb"><a href="{{ route('locations.index') }}">المواقع</a> <i class="fa-solid fa-chevron-left"></i> <span>جديد</span></div>
<form action="{{ route('locations.store') }}" method="POST" class="card card-body" style="max-width:680px">
    @csrf
    @include('locations._form', ['location' => null])
    <div class="flex gap-2 mt-5"><button class="btn btn-primary"><i class="fa-solid fa-save"></i> حفظ</button><a href="{{ route('locations.index') }}" class="btn btn-light">إلغاء</a></div>
</form>
@endsection
