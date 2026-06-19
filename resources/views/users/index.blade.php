@extends('layouts.app')
@section('title', 'المستخدمون')
@section('page-title', 'المستخدمون')
@section('page-sub', 'إدارة المستخدمين والأدوار')

@section('content')
<div class="page-head">
    <div class="titles"><h2>المستخدمون</h2><p>{{ $users->total() }} مستخدم</p></div>
    <div class="actions"><a href="{{ route('users.create') }}" class="btn btn-primary"><i class="fa-solid fa-user-plus"></i> مستخدم جديد</a></div>
</div>

<form method="GET" class="filter-bar">
    <input type="text" name="search" class="form-control" style="max-width:260px" placeholder="بحث بالاسم أو البريد..." value="{{ request('search') }}">
    <select name="role" class="form-select" style="max-width:200px" onchange="this.form.submit()">
        <option value="">كل الأدوار</option>
        @foreach($roles as $r)<option value="{{ $r->name }}" @selected(request('role')==$r->name)>{{ \App\Support\Roles::label($r->name) }}</option>@endforeach
    </select>
    <button class="btn btn-outline btn-icon"><i class="fa-solid fa-magnifying-glass"></i></button>
</form>

<div class="card"><div class="table-wrap"><table class="table">
    <thead><tr><th>المستخدم</th><th>الدور</th><th>القسم</th><th>الموقع</th><th>الهاتف</th><th>الحالة</th><th></th></tr></thead>
    <tbody>
        @forelse($users as $u)
            <tr>
                <td><div class="flex items-center gap-3"><x-avatar :user="$u" /><div><div class="cell-title">{{ $u->name }}</div><div class="cell-sub">{{ $u->email }}</div></div></div></td>
                <td>@foreach($u->roles as $r)<span class="badge badge-{{ \App\Support\Roles::color($r->name) }}">{{ \App\Support\Roles::label($r->name) }}</span>@endforeach</td>
                <td>{{ $u->department?->name ?? '—' }}</td>
                <td class="cell-sub">{{ $u->location?->name ?? '—' }}</td>
                <td class="cell-sub">{{ $u->phone ?? '—' }}</td>
                <td>@if($u->is_active)<span class="badge badge-green badge-dot">نشط</span>@else<span class="badge badge-gray badge-dot">معطّل</span>@endif</td>
                <td><div class="flex gap-2">
                    <a href="{{ route('users.edit', $u) }}" class="icon-btn" style="width:34px;height:34px"><i class="fa-solid fa-pen"></i></a>
                    <form action="{{ route('users.destroy', $u) }}" method="POST" onsubmit="return confirm('حذف المستخدم؟')">@csrf @method('DELETE')<button class="icon-btn" style="width:34px;height:34px;color:var(--danger)"><i class="fa-solid fa-trash"></i></button></form>
                </div></td>
            </tr>
        @empty
            <tr><td colspan="6"><x-empty icon="fa-users" title="لا يوجد مستخدمون" /></td></tr>
        @endforelse
    </tbody>
</table></div></div>
<div class="pagination-wrap">{{ $users->links() }}</div>
@endsection
