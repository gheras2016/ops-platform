@extends('layouts.app')
@section('title', 'المواقع')
@section('page-title', 'المواقع')
@section('page-sub', 'إدارة المباني والأدوار والغرف')

@section('content')
<div class="page-head">
    <div class="titles"><h2>المواقع</h2><p>{{ $locations->total() }} موقع</p></div>
    <div class="actions"><a href="{{ route('locations.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> موقع جديد</a></div>
</div>

<div class="card"><div class="table-wrap"><table class="table">
    <thead><tr><th>الموقع</th><th>النوع</th><th>يتبع</th><th>مواقع فرعية</th><th>مستخدمون</th><th>تذاكر</th><th></th></tr></thead>
    <tbody>
        @php($icons = ['building'=>'fa-building','floor'=>'fa-layer-group','room'=>'fa-door-closed','area'=>'fa-map-location-dot'])
        @forelse($locations as $loc)
            <tr>
                <td><div class="flex items-center gap-3">
                    <div class="stat-icon soft-indigo" style="width:38px;height:38px;margin:0;font-size:14px;border-radius:10px"><i class="fa-solid {{ $icons[$loc->type] ?? 'fa-location-dot' }}"></i></div>
                    <div><div class="cell-title">{{ $loc->name }}</div><div class="cell-sub">{{ $loc->full_path }}</div></div>
                </div></td>
                <td><span class="badge badge-slate">{{ $loc->typeLabel() }}</span></td>
                <td class="cell-sub">{{ $loc->parent?->name ?? '—' }}</td>
                <td>{{ $loc->children_count }}</td>
                <td>{{ $loc->users_count }}</td>
                <td>{{ $loc->tickets_count }}</td>
                <td><div class="flex gap-2">
                    <a href="{{ route('locations.show', $loc) }}" class="icon-btn" style="width:34px;height:34px"><i class="fa-solid fa-eye"></i></a>
                    <a href="{{ route('locations.edit', $loc) }}" class="icon-btn" style="width:34px;height:34px"><i class="fa-solid fa-pen"></i></a>
                    <form action="{{ route('locations.destroy', $loc) }}" method="POST" onsubmit="return confirm('حذف الموقع؟')">@csrf @method('DELETE')<button class="icon-btn" style="width:34px;height:34px;color:var(--danger)"><i class="fa-solid fa-trash"></i></button></form>
                </div></td>
            </tr>
        @empty
            <tr><td colspan="7"><x-empty icon="fa-map-location-dot" title="لا توجد مواقع" sub="ابدأ بإضافة مبنى" /></td></tr>
        @endforelse
    </tbody>
</table></div></div>
<div class="pagination-wrap">{{ $locations->links() }}</div>
@endsection
