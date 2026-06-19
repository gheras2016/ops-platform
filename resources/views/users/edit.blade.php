@extends('layouts.app')
@section('title', 'تعديل المستخدم')
@section('page-title', 'تعديل المستخدم')

@section('content')
<div class="breadcrumb"><a href="{{ route('users.index') }}">المستخدمون</a> <i class="fa-solid fa-chevron-left"></i> <span>{{ $user->name }}</span></div>
<form action="{{ route('users.update', $user) }}" method="POST" class="card card-body" style="max-width:820px">
    @csrf @method('PUT')
    @include('users._form')
    <div class="flex gap-2 mt-5"><button class="btn btn-primary"><i class="fa-solid fa-save"></i> حفظ التعديلات</button><a href="{{ route('users.index') }}" class="btn btn-light">إلغاء</a></div>
</form>
@endsection
