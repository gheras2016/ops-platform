@extends('layouts.app')
@section('title', 'الأصناف')
@section('page-title', 'الأصناف')
@section('page-sub', 'مخزون الأصناف العامة')

@section('content')
<div class="page-head">
    <div class="titles"><h2>الأصناف</h2><p>{{ $items->total() }} صنف</p></div>
    <div class="actions">
        <button type="button" class="btn btn-outline" onclick="openModal('importModal')"><i class="fa-solid fa-file-import"></i> استيراد</button>
        <a href="{{ route('inventory.items.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> صنف جديد</a>
    </div>
</div>

@include('partials.import-modal', ['importRoute' => route('inventory.items.import'), 'templateRoute' => route('inventory.items.template'), 'title' => 'الأصناف'])
<form method="GET" class="filter-bar"><input type="text" name="search" class="form-control" style="max-width:260px" placeholder="بحث..." value="{{ request('search') }}"><button class="btn btn-outline btn-icon"><i class="fa-solid fa-magnifying-glass"></i></button></form>
<div class="card"><div class="table-wrap"><table class="table">
    <thead><tr><th>الصنف</th><th>الفئة</th><th>الكمية</th><th>الوحدة</th><th>الموقع</th><th></th></tr></thead>
    <tbody>
        @forelse($items as $it)
            <tr>
                <td><div class="cell-title">{{ $it->name }}</div><div class="cell-sub">{{ $it->code }}</div></td>
                <td>{{ $it->category?->name ?? '—' }}</td>
                <td class="fw-700">{{ $it->quantity }}</td>
                <td class="cell-sub">{{ $it->unit ?? '—' }}</td>
                <td class="cell-sub">{{ $it->location ?? '—' }}</td>
                <td><div class="flex gap-2">
                    <a href="{{ route('inventory.items.edit', $it) }}" class="icon-btn" style="width:34px;height:34px"><i class="fa-solid fa-pen"></i></a>
                    <form action="{{ route('inventory.items.destroy', $it) }}" method="POST" onsubmit="return confirm('حذف؟')">@csrf @method('DELETE')<button class="icon-btn" style="width:34px;height:34px;color:var(--danger)"><i class="fa-solid fa-trash"></i></button></form>
                </div></td>
            </tr>
        @empty
            <tr><td colspan="6"><x-empty icon="fa-boxes-stacked" title="لا توجد أصناف" /></td></tr>
        @endforelse
    </tbody>
</table></div></div>
<div class="pagination-wrap">{{ $items->links() }}</div>
@endsection
