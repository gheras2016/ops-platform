@extends('layouts.app')
@section('title', 'قطع الغيار')
@section('page-title', 'قطع الغيار')
@section('page-sub', 'إدارة مخزون قطع الغيار')

@section('content')
<div class="page-head">
    <div class="titles"><h2>قطع الغيار</h2><p>{{ $spareParts->total() }} قطعة</p></div>
    <div class="actions">
        <button type="button" class="btn btn-outline" onclick="openModal('importModal')"><i class="fa-solid fa-file-import"></i> استيراد</button>
        <a href="{{ route('spare-parts.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> قطعة جديدة</a>
    </div>
</div>

@include('partials.import-modal', ['importRoute' => route('spare-parts.import'), 'templateRoute' => route('spare-parts.template'), 'title' => 'قطع الغيار'])

<form method="GET" class="filter-bar">
    <input type="text" name="search" class="form-control" style="max-width:240px" placeholder="بحث بالاسم أو الرقم..." value="{{ request('search') }}">
    <select name="category" class="form-select" style="max-width:200px" onchange="this.form.submit()">
        <option value="">كل التصنيفات</option>
        @foreach($categories as $cat)
            <option value="{{ $cat->id }}" @selected(request('category') == $cat->id)>{{ $cat->name }}</option>
        @endforeach
    </select>
    <a href="{{ route('spare-parts.index', ['low_stock'=>1]) }}" class="btn {{ request('low_stock') ? 'btn-warning' : 'btn-outline' }}"><i class="fa-solid fa-triangle-exclamation"></i> مخزون منخفض</a>
    <button class="btn btn-outline btn-icon"><i class="fa-solid fa-magnifying-glass"></i></button>
</form>

<div class="card"><div class="table-wrap"><table class="table">
    <thead><tr><th>القطعة</th><th>الرقم</th><th>التصنيف</th><th>القسم</th><th>الكمية</th><th>الحد الأدنى</th><th>الحالة</th><th></th></tr></thead>
    <tbody>
        @forelse($spareParts as $p)
            <tr>
                <td class="cell-title">{{ $p->name }}</td>
                <td class="cell-sub">{{ $p->part_number }}</td>
                <td>{{ $p->category?->name ?? '—' }}</td>
                <td>
                    @if($p->category?->department)
                        <span class="badge badge-{{ $p->category->department->color ?: 'slate' }}">{{ $p->category->department->name }}</span>
                    @else
                        <span class="badge badge-teal" title="يظهر لكل الأقسام"><i class="fa-solid fa-globe"></i> عام</span>
                    @endif
                </td>
                <td class="fw-700">{{ $p->quantity }}</td>
                <td class="cell-sub">{{ $p->min_stock }}</td>
                <td>@if($p->isLowStock())<span class="badge badge-red badge-dot">منخفض</span>@else<span class="badge badge-green badge-dot">متوفر</span>@endif</td>
                <td><div class="flex gap-2">
                    <a href="{{ route('spare-parts.edit', $p) }}" class="icon-btn" style="width:34px;height:34px"><i class="fa-solid fa-pen"></i></a>
                    <form action="{{ route('spare-parts.destroy', $p) }}" method="POST" onsubmit="return confirm('حذف؟')">@csrf @method('DELETE')<button class="icon-btn" style="width:34px;height:34px;color:var(--danger)"><i class="fa-solid fa-trash"></i></button></form>
                </div></td>
            </tr>
        @empty
            <tr><td colspan="8"><x-empty icon="fa-gears" title="لا توجد قطع غيار" /></td></tr>
        @endforelse
    </tbody>
</table></div></div>
<div class="pagination-wrap">{{ $spareParts->links() }}</div>
@endsection
