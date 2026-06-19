@extends('layouts.app')
@section('title', 'تعديل الصنف')
@section('page-title', 'تعديل الصنف')
@section('content')
<div class="breadcrumb"><a href="{{ route('inventory.items.index') }}">الأصناف</a> <i class="fa-solid fa-chevron-left"></i> <span>{{ $item->name }}</span></div>
<form action="{{ route('inventory.items.update', $item) }}" method="POST" class="card card-body" style="max-width:760px">@csrf @method('PUT')
    @include('inventory.items._form')
    <div class="flex gap-2 mt-5"><button class="btn btn-primary"><i class="fa-solid fa-save"></i> حفظ</button><a href="{{ route('inventory.items.index') }}" class="btn btn-light">إلغاء</a></div>
</form>
@endsection
