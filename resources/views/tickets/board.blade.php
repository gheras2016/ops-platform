@extends('layouts.app')
@section('title', 'لوحة المهام')
@section('page-title', 'لوحة المهام (كانبان)')
@section('page-sub', 'عرض التذاكر حسب مرحلة التنفيذ')

@section('content')
<div class="page-head">
    <div class="titles"><h2>لوحة المهام</h2><p>تابع تقدّم التذاكر عبر المراحل</p></div>
    <div class="actions">
        <div class="seg">
            <a href="{{ route('tickets.index') }}"><i class="fa-solid fa-list"></i> قائمة</a>
            <a href="{{ route('tickets.board') }}" class="active"><i class="fa-solid fa-table-columns"></i> لوحة</a>
        </div>
        <a href="{{ route('tickets.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> تذكرة</a>
    </div>
</div>

<form method="GET" class="filter-bar">
    <select name="department" class="form-select" style="max-width:220px" onchange="this.form.submit()">
        <option value="">كل الأقسام</option>
        @foreach($departments as $d)<option value="{{ $d->id }}" @selected(request('department')==$d->id)>{{ $d->name }}</option>@endforeach
    </select>
</form>

@php($palette = \App\Models\Ticket::STATUSES)
<div class="board">
    @foreach($columns as $status)
        @php($items = $grouped[$status] ?? collect())
        @php($color = $palette[$status][1] ?? 'gray')
        <div class="board-col">
            <div class="board-col-head">
                <span class="badge badge-{{ $color }} badge-dot">{{ $palette[$status][0] }}</span>
                <span class="count">{{ $items->count() }}</span>
            </div>
            <div class="board-col-body">
                @forelse($items as $t)
                    <a href="{{ route('tickets.show', $t) }}" class="board-card" style="border-right-color:var(--{{ $t->priority?->color ?: 'gray' }})">
                        <div class="bc-num">{{ $t->ticket_number }}</div>
                        <div class="bc-title">{{ $t->title }}</div>
                        <div class="flex items-center gap-2 mb-3" style="flex-wrap:wrap">
                            <span class="badge badge-{{ $t->department?->color ?: 'slate' }}" style="font-size:11px">{{ $t->department?->name }}</span>
                            <x-priority-badge :priority="$t->priority" style="font-size:11px" />
                        </div>
                        <x-progress :value="$t->progress" :showLabel="false" />
                        <div class="bc-foot mt-3">
                            @if($t->technician)
                                <span class="flex items-center gap-2"><x-avatar :user="$t->technician" size="sm" /><span class="text-xs">{{ $t->technician->name }}</span></span>
                            @else<span class="text-xs text-soft">غير مسند</span>@endif
                            <span class="text-xs text-soft">{{ $t->created_at->diffForHumans() }}</span>
                        </div>
                    </a>
                @empty
                    <div class="board-empty">لا توجد تذاكر</div>
                @endforelse
            </div>
        </div>
    @endforeach
</div>
@endsection
