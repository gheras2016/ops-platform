@extends('layouts.app')
@section('title', $department->name)
@section('page-title', $department->name)
@section('page-sub', $department->typeLabel())

@section('content')
<div class="breadcrumb"><a href="{{ route('departments.index') }}">الأقسام</a> <i class="fa-solid fa-chevron-left"></i> <span>{{ $department->name }}</span></div>

<div class="detail-grid">
    <div class="card">
        <div class="card-header"><h3>أعضاء القسم</h3><span class="sub">{{ $department->members->count() }} عضو</span></div>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>الاسم</th><th>الدور</th><th>البريد</th></tr></thead>
            <tbody>
                @forelse($department->members as $m)
                    <tr>
                        <td><div class="flex items-center gap-2"><x-avatar :user="$m" size="sm" /><span class="cell-title">{{ $m->name }}</span></div></td>
                        <td><span class="badge badge-{{ \App\Support\Roles::color($m->roles->first()?->name) }}">{{ \App\Support\Roles::label($m->roles->first()?->name) }}</span></td>
                        <td class="cell-sub">{{ $m->email }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3"><x-empty icon="fa-users" title="لا يوجد أعضاء" /></td></tr>
                @endforelse
            </tbody>
        </table></div>
    </div>

    <div class="card card-body">
        <div class="flex items-center gap-3 mb-4">
            <div class="stat-icon soft-{{ $department->color ?: 'slate' }}" style="margin:0"><i class="fa-solid fa-sitemap"></i></div>
            <div><div class="fw-700">{{ $department->name }}</div><div class="text-sm text-muted">{{ $department->code }}</div></div>
        </div>
        <div class="kv"><span class="k">النوع</span><span class="v">{{ $department->typeLabel() }}</span></div>
        <div class="kv"><span class="k">رئيس القسم</span><span class="v">{{ $department->head?->name ?? 'غير محدد' }}</span></div>
        <div class="kv"><span class="k">عدد التذاكر</span><span class="v">{{ $department->tickets->count() }}</span></div>
        <a href="{{ route('departments.edit', $department) }}" class="btn btn-light btn-block mt-4"><i class="fa-solid fa-pen"></i> تعديل</a>
    </div>
</div>
@endsection
