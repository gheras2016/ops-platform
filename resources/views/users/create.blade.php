@extends('layouts.app')
@section('title', 'مستخدم جديد')
@section('page-title', 'مستخدم جديد')

@section('content')
<div class="breadcrumb"><a href="{{ route('users.index') }}">المستخدمون</a> <i class="fa-solid fa-chevron-left"></i> <span>جديد</span></div>
<form action="{{ route('users.store') }}" method="POST" class="card card-body" style="max-width:820px">
    @csrf
    @include('users._form', ['user' => null])
    <div class="flex gap-2 mt-5"><button class="btn btn-primary"><i class="fa-solid fa-save"></i> إنشاء</button><a href="{{ route('users.index') }}" class="btn btn-light">إلغاء</a></div>
</form>
@endsection
