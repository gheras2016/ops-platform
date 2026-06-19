@extends('layouts.app')
@section('title', 'طلبات الشراء')
@section('page-title', 'طلبات الشراء والتوريد')
@section('page-sub', 'سلسلة الاعتماد: الإدارة ← المالية ← التنفيذ')

@section('content')
@php($user = auth()->user())
<div class="page-head">
    <div class="titles"><h2>طلبات الشراء</h2><p>متابعة الطلبات واعتمادها وتنفيذها</p></div>
    @can('create', \App\Models\PurchaseRequest::class)
        <div class="actions"><a href="{{ route('purchase-requests.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> طلب شراء جديد</a></div>
    @endcan
</div>

@php($row = function($pr){ return $pr; })

<div class="card mb-5">
    <div class="card-header"><h3><i class="fa-solid fa-bell text-muted"></i> بانتظار إجرائك</h3><span class="sub">{{ $actionable->count() }}</span></div>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>رقم الطلب</th><th>النوع</th><th>القسم</th><th>المرحلة الحالية</th><th>الأصناف</th><th></th></tr></thead>
        <tbody>
            @forelse($actionable as $pr)
                <tr>
                    <td class="cell-title">{{ $pr->request_number }}@if($pr->ticket_id)<div class="cell-sub">تذكرة {{ $pr->ticket?->ticket_number }}</div>@endif</td>
                    <td><span class="badge badge-{{ $pr->isDirect() ? 'orange' : 'blue' }}">{{ $pr->typeLabel() }}</span></td>
                    <td>{{ $pr->department?->name ?? '—' }}</td>
                    <td><span class="badge badge-{{ $pr->statusColor() }} badge-dot">{{ $pr->statusLabel() }}</span>@if($pr->current_dept_id)<div class="cell-sub">لدى: {{ $pr->currentDept?->name }}</div>@endif</td>
                    <td>{{ $pr->items->count() }} صنف</td>
                    <td><a href="{{ route('purchase-requests.show', $pr) }}" class="btn btn-primary btn-sm">مراجعة</a></td>
                </tr>
            @empty
                <tr><td colspan="6"><x-empty icon="fa-check-double" title="لا شيء بانتظار إجرائك" /></td></tr>
            @endforelse
        </tbody>
    </table></div>
</div>

<div class="card">
    <div class="card-header"><h3><i class="fa-solid fa-file-lines text-muted"></i> طلباتي</h3><span class="sub">{{ $mine->count() }}</span></div>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>رقم الطلب</th><th>النوع</th><th>الحالة</th><th>التاريخ</th><th></th></tr></thead>
        <tbody>
            @forelse($mine as $pr)
                <tr>
                    <td class="cell-title">{{ $pr->request_number }}</td>
                    <td><span class="badge badge-{{ $pr->isDirect() ? 'orange' : 'blue' }}">{{ $pr->typeLabel() }}</span></td>
                    <td><span class="badge badge-{{ $pr->statusColor() }} badge-dot">{{ $pr->statusLabel() }}</span></td>
                    <td class="cell-sub">{{ $pr->created_at->diffForHumans() }}</td>
                    <td><a href="{{ route('purchase-requests.show', $pr) }}" class="btn btn-outline btn-sm">عرض</a></td>
                </tr>
            @empty
                <tr><td colspan="5"><x-empty icon="fa-file-circle-plus" title="لم تنشئ طلبات بعد" /></td></tr>
            @endforelse
        </tbody>
    </table></div>
</div>
@endsection
