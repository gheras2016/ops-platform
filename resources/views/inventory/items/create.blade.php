@extends('layouts.app')
@section('title', 'صنف جديد')
@section('page-title', 'صنف جديد')
@section('content')
<div class="breadcrumb"><a href="{{ route('inventory.items.index') }}">الأصناف</a> <i class="fa-solid fa-chevron-left"></i> <span>جديد</span></div>
<form action="{{ route('inventory.items.store') }}" method="POST" class="card card-body" style="max-width:760px">@csrf
    @include('inventory.items._form', ['item' => null])
    <div class="flex gap-2 mt-5"><button class="btn btn-primary"><i class="fa-solid fa-save"></i> حفظ</button><a href="{{ route('inventory.items.index') }}" class="btn btn-light">إلغاء</a></div>
</form>
@endsection
