@extends('layouts.app')
@section('title', 'طلبات الإسبير')
@section('page-title', 'طلبات صرف الإسبير')
@section('page-sub', 'اعتماد وصرف قطع الغيار المرتبطة بالبلاغات')

@section('content')
@php($user = auth()->user())

@if($user->isAdmin() || $user->isDepartmentHead())
<div class="card mb-5">
    <div class="card-header"><h3><i class="fa-solid fa-clipboard-check text-muted"></i> بانتظار اعتمادك</h3><span class="sub">{{ $pendingApprovals->count() }}</span></div>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>رقم الطلب</th><th>التذكرة</th><th>القسم</th><th>الفني</th><th>القطع</th><th>التاريخ</th><th></th></tr></thead>
        <tbody>
            @forelse($pendingApprovals as $pr)
                <tr>
                    <td class="cell-title">{{ $pr->request_number }}</td>
                    <td><a href="{{ route('tickets.show', $pr->ticket) }}" style="color:var(--primary)">{{ $pr->ticket?->ticket_number }}</a></td>
                    <td>{{ $pr->department?->name ?? '—' }}</td>
                    <td class="cell-sub">{{ $pr->requester?->name }}</td>
                    <td>{{ $pr->items->count() }} صنف</td>
                    <td class="cell-sub">{{ $pr->created_at->diffForHumans() }}</td>
                    <td><a href="{{ route('tickets.show', $pr->ticket) }}" class="btn btn-outline btn-sm">مراجعة</a></td>
                </tr>
            @empty
                <tr><td colspan="7"><x-empty icon="fa-clipboard-check" title="لا توجد طلبات بانتظار الاعتماد" /></td></tr>
            @endforelse
        </tbody>
    </table></div>
</div>
@endif

@if($user->canManageInventory())
<div class="card">
    <div class="card-header"><h3><i class="fa-solid fa-dolly text-muted"></i> بانتظار الصرف من المخزون</h3><span class="sub">{{ $toIssue->count() }}</span></div>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>رقم الطلب</th><th>التذكرة</th><th>القسم</th><th>الحالة</th><th>القطع</th><th></th></tr></thead>
        <tbody>
            @forelse($toIssue as $pr)
                <tr>
                    <td class="cell-title">{{ $pr->request_number }}</td>
                    <td><a href="{{ route('tickets.show', $pr->ticket) }}" style="color:var(--primary)">{{ $pr->ticket?->ticket_number }}</a></td>
                    <td>{{ $pr->department?->name ?? '—' }}</td>
                    <td><span class="badge badge-{{ $pr->statusColor() }} badge-dot">{{ $pr->statusLabel() }}</span></td>
                    <td>{{ $pr->items->count() }} صنف</td>
                    <td><a href="{{ route('tickets.show', $pr->ticket) }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-dolly"></i> صرف</a></td>
                </tr>
            @empty
                <tr><td colspan="6"><x-empty icon="fa-box-open" title="لا توجد طلبات بانتظار الصرف" /></td></tr>
            @endforelse
        </tbody>
    </table></div>
</div>
@endif
@endsection
