@extends('layouts.app')
@section('title', 'حركات المخزون')
@section('page-title', 'حركات المخزون')
@section('page-sub', 'سجل إدخال وإخراج قطع الغيار')

@section('content')
<div class="page-head">
    <div class="titles"><h2>حركات المخزون</h2></div>
    <div class="actions"><a href="{{ route('stock-transactions.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> حركة جديدة</a></div>
</div>
<div class="card"><div class="table-wrap"><table class="table">
    <thead><tr><th>القطعة</th><th>النوع</th><th>الكمية</th><th>المستخدم</th><th>التاريخ</th></tr></thead>
    <tbody>
        @forelse($transactions as $t)
            <tr>
                <td class="cell-title">{{ $t->sparePart?->name ?? '—' }}</td>
                <td>@if($t->type==='in')<span class="badge badge-green"><i class="fa-solid fa-arrow-down"></i> إدخال</span>@else<span class="badge badge-red"><i class="fa-solid fa-arrow-up"></i> إخراج</span>@endif</td>
                <td class="fw-700">{{ $t->quantity }}</td>
                <td class="cell-sub">{{ $t->user?->name ?? '—' }}</td>
                <td class="cell-sub">{{ $t->created_at->format('Y-m-d H:i') }}</td>
            </tr>
        @empty
            <tr><td colspan="5"><x-empty icon="fa-right-left" title="لا توجد حركات" /></td></tr>
        @endforelse
    </tbody>
</table></div></div>
<div class="pagination-wrap">{{ $transactions->links() }}</div>
@endsection
