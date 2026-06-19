@extends('layouts.app')
@section('title', 'الشركات')
@section('page-title', 'الشركات')
@section('page-sub', 'إدارة الشركات المشتركة في المنصة')

@section('content')
<div class="page-head">
    <div class="titles"><h2>الشركات</h2><p>{{ $companies->total() }} شركة</p></div>
    <div class="actions"><a href="{{ route('companies.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> شركة جديدة</a></div>
</div>

<div class="card"><div class="table-wrap"><table class="table">
    <thead><tr><th>الشركة</th><th>المستخدمون</th><th>الأقسام</th><th>التذاكر</th><th>الحالة</th><th></th></tr></thead>
    <tbody>
        @forelse($companies as $c)
            <tr>
                <td><div class="flex items-center gap-3"><div class="stat-icon soft-indigo" style="margin:0;width:40px;height:40px"><i class="fa-solid fa-building"></i></div><div><div class="cell-title">{{ $c->name }}</div><div class="cell-sub">{{ $c->code }} · {{ $c->email }}</div></div></div></td>
                <td>{{ $c->users_count }}</td>
                <td>{{ $c->departments_count }}</td>
                <td>{{ $c->tickets_count }}</td>
                <td>@if($c->is_active)<span class="badge badge-green badge-dot">نشطة</span>@else<span class="badge badge-gray badge-dot">معطّلة</span>@endif</td>
                <td><div class="flex gap-2">
                    <a href="{{ route('companies.export', $c) }}" class="icon-btn" style="width:34px;height:34px" title="تصدير بيانات الشركة"><i class="fa-solid fa-download"></i></a>
                    <form action="{{ route('companies.toggle', $c) }}" method="POST" title="{{ $c->is_active ? 'إيقاف' : 'تفعيل' }}">@csrf<button class="icon-btn" style="width:34px;height:34px;color:{{ $c->is_active ? 'var(--warning, #d97706)' : 'var(--success, #16a34a)' }}"><i class="fa-solid {{ $c->is_active ? 'fa-pause' : 'fa-play' }}"></i></button></form>
                    <a href="{{ route('companies.edit', $c) }}" class="icon-btn" style="width:34px;height:34px"><i class="fa-solid fa-pen"></i></a>
                    <form action="{{ route('companies.destroy', $c) }}" method="POST" onsubmit="return confirm('حذف الشركة وكل بياناتها؟')">@csrf @method('DELETE')<button class="icon-btn" style="width:34px;height:34px;color:var(--danger)"><i class="fa-solid fa-trash"></i></button></form>
                </div></td>
            </tr>
        @empty
            <tr><td colspan="6"><x-empty icon="fa-building" title="لا توجد شركات" /></td></tr>
        @endforelse
    </tbody>
</table></div></div>
<div class="pagination-wrap">{{ $companies->links() }}</div>
@endsection
