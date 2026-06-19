@extends('layouts.app')
@section('title', $location->name)
@section('page-title', $location->name)
@section('page-sub', $location->full_path)

@section('content')
<div class="breadcrumb"><a href="{{ route('locations.index') }}">المواقع</a> <i class="fa-solid fa-chevron-left"></i> <span>{{ $location->name }}</span></div>

<div class="detail-grid">
    <div class="card">
        <div class="card-header"><h3>المستخدمون في هذا الموقع</h3><span class="sub">{{ $location->users->count() }}</span></div>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>الاسم</th><th>الدور</th><th>البريد</th></tr></thead>
            <tbody>
                @forelse($location->users as $u)
                    <tr>
                        <td><div class="flex items-center gap-2"><x-avatar :user="$u" size="sm" /><span class="cell-title">{{ $u->name }}</span></div></td>
                        <td><span class="badge badge-{{ \App\Support\Roles::color($u->roles->first()?->name) }}">{{ \App\Support\Roles::label($u->roles->first()?->name) }}</span></td>
                        <td class="cell-sub">{{ $u->email }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3"><x-empty icon="fa-users" title="لا يوجد مستخدمون مرتبطون" /></td></tr>
                @endforelse
            </tbody>
        </table></div>
    </div>

    <div class="card card-body">
        <div class="kv"><span class="k">النوع</span><span class="v">{{ $location->typeLabel() }}</span></div>
        <div class="kv"><span class="k">المسار الكامل</span><span class="v">{{ $location->full_path }}</span></div>
        <div class="kv"><span class="k">يتبع</span><span class="v">{{ $location->parent?->name ?? 'موقع رئيسي' }}</span></div>
        <div class="kv"><span class="k">مواقع فرعية</span><span class="v">{{ $location->children->count() }}</span></div>

        @if($location->children->isNotEmpty())
            <div class="mt-3">
                <div class="form-label mb-2">المواقع الفرعية:</div>
                @foreach($location->children as $c)
                    <a href="{{ route('locations.show', $c) }}" class="badge badge-slate" style="margin:2px"><i class="fa-solid fa-diagram-successor"></i> {{ $c->name }}</a>
                @endforeach
            </div>
        @endif

        <a href="{{ route('locations.edit', $location) }}" class="btn btn-light btn-block mt-4"><i class="fa-solid fa-pen"></i> تعديل</a>
    </div>
</div>
@endsection
