@extends('layouts.app')
@section('title', 'الاشتراك')
@section('page-title', 'اشتراك المنشأة')
@section('page-sub', 'باقتك الحالية والتجديد والفواتير')

@php
    $colors = ['blue' => '#2563eb', 'green' => '#16a34a', 'amber' => '#d97706', 'red' => '#dc2626', 'gray' => '#64748b'];
    $sc = $colors[$company->subStatusColor()] ?? $colors['gray'];
    $days = $company->daysRemaining();
@endphp

@section('content')

{{-- Current status --}}
<div class="card" style="padding:18px;margin-bottom:18px">
    <div class="flex items-center" style="gap:14px;flex-wrap:wrap">
        <div>
            <div style="color:#64748b;font-size:13px">الحالة الحالية</div>
            <div style="font-size:20px;font-weight:800;color:{{ $sc }}">{{ $company->subStatusLabel() }}</div>
        </div>
        <div style="width:1px;height:36px;background:#e2e8f0"></div>
        <div>
            <div style="color:#64748b;font-size:13px">الباقة</div>
            <div style="font-weight:700">{{ $company->plan?->name ?? '—' }}</div>
        </div>
        <div style="width:1px;height:36px;background:#e2e8f0"></div>
        <div>
            <div style="color:#64748b;font-size:13px">المتبقّي</div>
            <div style="font-weight:700">
                @if(is_null($days)) بلا انتهاء
                @elseif($days < 0) <span style="color:{{ $colors['red'] }}">منتهٍ</span>
                @else {{ $days }} يوم @endif
            </div>
        </div>
    </div>
</div>

{{-- Plans --}}
<h3 style="margin:0 0 12px;font-size:16px;font-weight:800">اختر باقة</h3>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;margin-bottom:22px">
    @foreach($plans as $plan)
        <div class="card" style="padding:18px;display:flex;flex-direction:column">
            <div style="font-weight:800;font-size:18px">{{ $plan->name }}</div>
            <div style="margin:8px 0"><span style="font-size:26px;font-weight:800;color:#4f46e5">{{ number_format($plan->price, 0) }}</span>
                <span style="color:#64748b">{{ $plan->currency }} / {{ $plan->periodLabel() }}</span></div>
            <ul style="list-style:none;padding:0;margin:8px 0 16px;color:#334155;font-size:13px;flex:1">
                @foreach(($plan->features ?? []) as $f)
                    <li style="padding:3px 0"><i class="fa-solid fa-check" style="color:#16a34a"></i> {{ $f }}</li>
                @endforeach
            </ul>
            <form method="POST" action="{{ route('company.subscription.request') }}">
                @csrf
                <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                <button class="btn btn-primary btn-block"><i class="fa-solid fa-credit-card"></i> اشترك الآن</button>
            </form>
        </div>
    @endforeach
</div>

{{-- Invoices --}}
<h3 style="margin:0 0 12px;font-size:16px;font-weight:800">سجل الفواتير</h3>
<div class="card"><div class="table-wrap"><table class="table">
    <thead><tr><th>التاريخ</th><th>الباقة</th><th>المبلغ</th><th>الطريقة</th><th>الحالة</th></tr></thead>
    <tbody>
        @forelse($invoices as $inv)
            <tr>
                <td>{{ $inv->paid_at?->format('Y-m-d') ?? $inv->created_at->format('Y-m-d') }}</td>
                <td>{{ $inv->plan?->name ?? '—' }}</td>
                <td>{{ number_format($inv->amount, 2) }} {{ $inv->currency }}</td>
                <td>{{ $inv->method === 'manual' ? 'يدوي' : 'إلكتروني' }}</td>
                <td>@if($inv->isPaid())<span class="badge badge-green badge-dot">مدفوعة</span>@else<span class="badge badge-gray badge-dot">{{ $inv->status }}</span>@endif</td>
            </tr>
        @empty
            <tr><td colspan="5"><x-empty icon="fa-receipt" title="لا توجد فواتير بعد" /></td></tr>
        @endforelse
    </tbody>
</table></div></div>
@endsection
