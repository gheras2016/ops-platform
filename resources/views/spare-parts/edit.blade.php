@extends('layouts.app')
@section('title', 'تعديل قطعة الغيار')
@section('page-title', 'تعديل قطعة الغيار')
@section('content')
<div class="breadcrumb"><a href="{{ route('spare-parts.index') }}">قطع الغيار</a> <i class="fa-solid fa-chevron-left"></i> <span>{{ $sparePart->name }}</span></div>
<form action="{{ route('spare-parts.update', $sparePart) }}" method="POST" class="card card-body" style="max-width:760px">@csrf @method('PUT')
    @include('spare-parts._form')
    <div class="flex gap-2 mt-5"><button class="btn btn-primary"><i class="fa-solid fa-save"></i> حفظ</button><a href="{{ route('spare-parts.index') }}" class="btn btn-light">إلغاء</a></div>
</form>
@endsection
