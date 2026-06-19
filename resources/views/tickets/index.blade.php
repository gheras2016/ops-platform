@extends('layouts.app')
@section('title', 'التذاكر')
@section('page-title', 'التذاكر')
@section('page-sub', 'إدارة ومتابعة بلاغات الصيانة')

@section('content')
<div class="page-head">
    <div class="titles">
        <h2>التذاكر</h2>
        <p>إجمالي {{ $stats['all'] }} تذكرة ضمن نطاق صلاحياتك</p>
    </div>
    <div class="actions">
        <div class="seg">
            <a href="{{ route('tickets.index') }}" class="active"><i class="fa-solid fa-list"></i> قائمة</a>
            <a href="{{ route('tickets.board') }}"><i class="fa-solid fa-table-columns"></i> لوحة</a>
        </div>
        <a href="{{ route('tickets.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> تذكرة جديدة</a>
    </div>
</div>

{{-- Status filter segments --}}
<div class="seg mb-4">
    <a href="{{ route('tickets.index') }}" class="{{ !request('status') ? 'active' : '' }}">الكل <span class="count">{{ $stats['all'] }}</span></a>
    @foreach($statuses as $code => $info)
        <a href="{{ route('tickets.index', ['status' => $code]) }}" class="{{ request('status') == $code ? 'active' : '' }}">
            {{ $info[0] }} <span class="count">{{ $stats[$code] ?? 0 }}</span>
        </a>
    @endforeach
</div>

{{-- Filters --}}
<form method="GET" class="filter-bar">
    @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
    <input type="text" name="search" class="form-control" style="max-width:260px" placeholder="بحث بالرقم أو العنوان..." value="{{ request('search') }}">
    <select name="department" class="form-select" style="max-width:200px" onchange="this.form.submit()">
        <option value="">كل الأقسام</option>
        @foreach($departments as $d)
            <option value="{{ $d->id }}" @selected(request('department') == $d->id)>{{ $d->name }}</option>
        @endforeach
    </select>
    <select name="priority" class="form-select" style="max-width:170px" onchange="this.form.submit()">
        <option value="">كل الأولويات</option>
        @foreach($priorities as $p)
            <option value="{{ $p->id }}" @selected(request('priority') == $p->id)>{{ $p->name }}</option>
        @endforeach
    </select>
    <button class="btn btn-outline btn-icon"><i class="fa-solid fa-magnifying-glass"></i></button>
</form>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>التذكرة</th>
                    <th>القسم</th>
                    <th>مقدّم الطلب</th>
                    <th>الفني</th>
                    <th>الأولوية</th>
                    <th>الإنجاز</th>
                    <th>الحالة</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($tickets as $t)
                    <tr style="cursor:pointer" onclick="window.location='{{ route('tickets.show', $t) }}'">
                        <td>
                            <div class="cell-title">{{ $t->title }}</div>
                            <div class="cell-sub">{{ $t->ticket_number }} · {{ $t->created_at->diffForHumans() }}
                                @if($t->isOverdue())<span class="badge badge-red" style="margin-right:6px"><i class="fa-solid fa-clock"></i> متأخرة</span>@endif
                            </div>
                        </td>
                        <td><span class="badge badge-{{ $t->department?->color ?: 'slate' }}">{{ $t->department?->name ?? '—' }}</span></td>
                        <td>
                            <div class="flex items-center gap-2"><x-avatar :user="$t->creator" size="sm" /><span class="text-sm">{{ $t->creator?->name ?? '—' }}</span></div>
                        </td>
                        <td>
                            @if($t->technician)
                                <div class="flex items-center gap-2"><x-avatar :user="$t->technician" size="sm" /><span class="text-sm">{{ $t->technician->name }}</span></div>
                            @else <span class="text-soft text-sm">غير مسند</span> @endif
                        </td>
                        <td><x-priority-badge :priority="$t->priority" /></td>
                        <td style="min-width:120px"><x-progress :value="$t->progress" :showLabel="false" /></td>
                        <td><x-status-badge :status="$t->status" /></td>
                        <td><i class="fa-solid fa-chevron-left text-soft"></i></td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-empty icon="fa-ticket" title="لا توجد تذاكر" sub="لم يتم العثور على تذاكر مطابقة" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="pagination-wrap">{{ $tickets->links() }}</div>
@endsection
