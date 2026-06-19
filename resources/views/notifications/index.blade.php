@extends('layouts.app')
@section('title', 'الإشعارات')
@section('page-title', 'الإشعارات')
@section('page-sub', 'كل التنبيهات الخاصة بك')

@section('content')
<div class="page-head">
    <div class="titles"><h2>الإشعارات</h2><p>{{ $notifications->total() }} إشعار</p></div>
    <div class="actions">
        @if(auth()->user()->unreadNotifications()->count())
            <form action="{{ route('notifications.read-all') }}" method="POST">@csrf<button class="btn btn-outline"><i class="fa-solid fa-check-double"></i> تعليم الكل كمقروء</button></form>
        @endif
    </div>
</div>

<div class="card">
    @forelse($notifications as $n)
        <a href="{{ route('notifications.read', $n->id) }}" class="flex items-center gap-3" style="padding:16px 20px; border-bottom:1px solid var(--border); {{ $n->read_at ? '' : 'background:var(--primary-soft)' }}">
            <div class="stat-icon soft-{{ $n->data['color'] ?? 'gray' }}" style="width:42px;height:42px;margin:0;border-radius:11px"><i class="fa-solid {{ $n->data['icon'] ?? 'fa-bell' }}"></i></div>
            <div style="flex:1">
                <div class="fw-600">{{ $n->data['message'] ?? '' }}</div>
                <div class="text-xs text-muted mt-1">{{ $n->data['ticket_number'] ?? '' }} · {{ $n->created_at->diffForHumans() }}</div>
            </div>
            @if(!$n->read_at)<span class="badge badge-blue badge-dot">جديد</span>@endif
            <i class="fa-solid fa-chevron-left text-soft"></i>
        </a>
    @empty
        <x-empty icon="fa-bell-slash" title="لا توجد إشعارات" sub="ستظهر هنا تنبيهات تذاكرك" />
    @endforelse
</div>
<div class="pagination-wrap">{{ $notifications->links() }}</div>
@endsection
