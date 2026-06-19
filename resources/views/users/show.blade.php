@extends('layouts.app')
@section('title', $user->name)
@section('page-title', $user->name)

@section('content')
<div class="breadcrumb"><a href="{{ route('users.index') }}">المستخدمون</a> <i class="fa-solid fa-chevron-left"></i> <span>{{ $user->name }}</span></div>
<div class="card card-body" style="max-width:560px">
    <div class="flex items-center gap-3 mb-4"><x-avatar :user="$user" size="lg" /><div><div class="fw-700" style="font-size:18px">{{ $user->name }}</div><div class="text-muted">{{ $user->email }}</div></div></div>
    <div class="kv"><span class="k">الدور</span><span class="v">@foreach($user->roles as $r)<span class="badge badge-{{ \App\Support\Roles::color($r->name) }}">{{ \App\Support\Roles::label($r->name) }}</span>@endforeach</span></div>
    <div class="kv"><span class="k">القسم</span><span class="v">{{ $user->department?->name ?? '—' }}</span></div>
    <div class="kv"><span class="k">الموقع</span><span class="v">{{ $user->location?->full_path ?? '—' }}</span></div>
    <div class="kv"><span class="k">الهاتف</span><span class="v">{{ $user->phone ?? '—' }}</span></div>
    <div class="kv"><span class="k">الحالة</span><span class="v">{{ $user->is_active ? 'نشط' : 'معطّل' }}</span></div>
    <a href="{{ route('users.edit', $user) }}" class="btn btn-light btn-block mt-4"><i class="fa-solid fa-pen"></i> تعديل</a>
</div>
@endsection
