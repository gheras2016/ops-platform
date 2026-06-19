@extends('layouts.app')
@section('title', 'تصنيفات الإسبير')
@section('page-title', 'تصنيفات قطع الغيار')
@section('page-sub', 'صنّف الإسبيرات واربطها بالأقسام')

@section('content')
<div class="page-head">
    <div class="titles"><h2>تصنيفات الإسبير</h2><p>{{ $categories->total() }} تصنيف · التصنيف بدون قسم يُعتبر عامًّا لكل الأقسام</p></div>
    <div class="actions"><a href="{{ route('spare-categories.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> تصنيف جديد</a></div>
</div>

<div class="card"><div class="table-wrap"><table class="table">
    <thead><tr><th>التصنيف</th><th>الرمز</th><th>القسم</th><th>عدد القطع</th><th></th></tr></thead>
    <tbody>
        @forelse($categories as $c)
            <tr>
                <td class="cell-title">{{ $c->name }}</td>
                <td class="cell-sub">{{ $c->code ?? '—' }}</td>
                <td>
                    @if($c->department)
                        <span class="badge badge-{{ $c->department->color ?: 'slate' }}">{{ $c->department->name }}</span>
                    @else
                        <span class="badge badge-teal"><i class="fa-solid fa-globe"></i> عام (كل الأقسام)</span>
                    @endif
                </td>
                <td>{{ $c->spare_parts_count }}</td>
                <td><div class="flex gap-2">
                    <a href="{{ route('spare-categories.edit', $c) }}" class="icon-btn" style="width:34px;height:34px"><i class="fa-solid fa-pen"></i></a>
                    <form action="{{ route('spare-categories.destroy', $c) }}" method="POST" onsubmit="return confirm('حذف التصنيف؟')">@csrf @method('DELETE')<button class="icon-btn" style="width:34px;height:34px;color:var(--danger)"><i class="fa-solid fa-trash"></i></button></form>
                </div></td>
            </tr>
        @empty
            <tr><td colspan="5"><x-empty icon="fa-tags" title="لا توجد تصنيفات" /></td></tr>
        @endforelse
    </tbody>
</table></div></div>
<div class="pagination-wrap">{{ $categories->links() }}</div>
@endsection
