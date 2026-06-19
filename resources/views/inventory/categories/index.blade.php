@extends('layouts.app')
@section('title', 'الفئات')
@section('page-title', 'الفئات')
@section('page-sub', 'فئات الأصناف')

@section('content')
<div class="page-head">
    <div class="titles"><h2>الفئات</h2><p>{{ $categories->total() }} فئة</p></div>
    <div class="actions"><a href="{{ route('inventory.categories.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> فئة جديدة</a></div>
</div>
<div class="card"><div class="table-wrap"><table class="table">
    <thead><tr><th>الفئة</th><th>الرمز</th><th>عدد الأصناف</th><th>الحالة</th><th></th></tr></thead>
    <tbody>
        @forelse($categories as $c)
            <tr>
                <td><div class="cell-title">{{ $c->name }}</div><div class="cell-sub">{{ $c->description }}</div></td>
                <td class="cell-sub">{{ $c->code ?? '—' }}</td>
                <td>{{ $c->items_count }}</td>
                <td><span class="badge badge-green badge-dot">{{ $c->status ?? 'active' }}</span></td>
                <td><div class="flex gap-2">
                    <a href="{{ route('inventory.categories.edit', $c) }}" class="icon-btn" style="width:34px;height:34px"><i class="fa-solid fa-pen"></i></a>
                    <form action="{{ route('inventory.categories.destroy', $c) }}" method="POST" onsubmit="return confirm('حذف؟')">@csrf @method('DELETE')<button class="icon-btn" style="width:34px;height:34px;color:var(--danger)"><i class="fa-solid fa-trash"></i></button></form>
                </div></td>
            </tr>
        @empty
            <tr><td colspan="5"><x-empty icon="fa-tags" title="لا توجد فئات" /></td></tr>
        @endforelse
    </tbody>
</table></div></div>
<div class="pagination-wrap">{{ $categories->links() }}</div>
@endsection
