@extends('layouts.app')
@section('title', 'الأقسام')
@section('page-title', 'الأقسام')
@section('page-sub', 'إدارة أقسام الصيانة ورؤسائها')

@section('content')
<div class="page-head">
    <div class="titles"><h2>الأقسام</h2><p>الأقسام مصنّفة حسب نوع المهام</p></div>
    <div class="actions"><a href="{{ route('departments.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> قسم جديد</a></div>
</div>

<div class="stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(280px,1fr))">
    @forelse($departments as $d)
        <div class="card card-body">
            <div class="flex items-center justify-between mb-3">
                <span class="badge badge-{{ $d->color ?: 'slate' }}">{{ $d->typeLabel() }}</span>
                <div class="flex gap-2">
                    <a href="{{ route('departments.show', $d) }}" class="icon-btn" style="width:34px;height:34px"><i class="fa-solid fa-eye"></i></a>
                    <a href="{{ route('departments.edit', $d) }}" class="icon-btn" style="width:34px;height:34px"><i class="fa-solid fa-pen"></i></a>
                </div>
            </div>
            <h3 class="fw-700" style="font-size:18px">{{ $d->name }}</h3>
            <p class="text-sm text-muted mb-3">{{ $d->code }}</p>
            <div class="kv"><span class="k">رئيس القسم</span><span class="v">{{ $d->head?->name ?? 'غير محدد' }}</span></div>
            <div class="kv"><span class="k">عدد الأعضاء</span><span class="v">{{ $d->members_count }}</span></div>
            <div class="kv"><span class="k">التذاكر</span><span class="v">{{ $d->tickets_count }}</span></div>
        </div>
    @empty
        <x-empty icon="fa-sitemap" title="لا توجد أقسام" sub="ابدأ بإضافة أول قسم" />
    @endforelse
</div>
<div class="pagination-wrap">{{ $departments->links() }}</div>
@endsection
