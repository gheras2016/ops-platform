@extends('layouts.app')
@section('title', 'قطعة غيار جديدة')
@section('page-title', 'قطعة غيار جديدة')
@section('content')
<div class="breadcrumb"><a href="{{ route('spare-parts.index') }}">قطع الغيار</a> <i class="fa-solid fa-chevron-left"></i> <span>جديدة</span></div>
<form action="{{ route('spare-parts.store') }}" method="POST" class="card card-body" style="max-width:760px">@csrf
    @include('spare-parts._form', ['sparePart' => null])
    <div class="flex gap-2 mt-5"><button class="btn btn-primary"><i class="fa-solid fa-save"></i> حفظ</button><a href="{{ route('spare-parts.index') }}" class="btn btn-light">إلغاء</a></div>
</form>
@endsection
