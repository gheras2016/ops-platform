@extends('layouts.app')
@section('title', 'الباقات')
@section('page-title', 'الباقات')
@section('page-sub', 'إدارة باقات الاشتراك التي تختار منها الشركات')

@php
    $periods = ['monthly' => 'شهري', 'yearly' => 'سنوي'];
    $field = fn ($label, $name, $type, $val, $extra = '') =>
        '<label style="display:block;font-size:12px;color:#64748b;margin-bottom:8px">' . $label .
        '<input ' . $extra . ' type="' . $type . '" name="' . $name . '" value="' . e($val) . '" class="form-control" style="margin-top:3px"></label>';
@endphp

@section('content')
@if(session('success'))<div class="alert alert-success" style="margin-bottom:16px"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger" style="margin-bottom:16px"><i class="fa-solid fa-circle-exclamation"></i> {{ $errors->first() }}</div>@endif

{{-- Create --}}
<div class="card" style="padding:18px;margin-bottom:20px">
    <h3 style="margin:0 0 14px;font-size:15px;font-weight:800">إضافة باقة جديدة</h3>
    <form method="POST" action="{{ route('plans.store') }}">
        @csrf
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px">
            {!! $field('الاسم', 'name', 'text', old('name'), 'required') !!}
            {!! $field('السعر', 'price', 'number', old('price', 0), 'step=0.01 required') !!}
            {!! $field('العملة', 'currency', 'text', old('currency', 'SAR'), 'maxlength=3') !!}
            <label style="display:block;font-size:12px;color:#64748b">نوع الدورة
                <select name="billing_period" class="form-control" style="margin-top:3px">
                    @foreach($periods as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                </select>
            </label>
            {!! $field('المدة (أيام)', 'duration_days', 'number', old('duration_days', 365), 'required') !!}
            {!! $field('الترتيب', 'sort', 'number', old('sort', 0)) !!}
        </div>
        <label style="display:block;font-size:12px;color:#64748b;margin-top:12px">المزايا (ميزة في كل سطر)
            <textarea name="features" rows="3" class="form-control" style="margin-top:3px">{{ old('features') }}</textarea>
        </label>
        <label class="switch" style="margin-top:12px"><input type="checkbox" name="is_active" value="1" checked><span class="track"></span><span class="text-sm">مفعّلة</span></label>
        <div style="margin-top:12px"><button class="btn btn-primary"><i class="fa-solid fa-plus"></i> إضافة الباقة</button></div>
    </form>
</div>

{{-- Existing plans --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:16px">
    @forelse($plans as $plan)
        <div class="card" style="padding:16px">
            <form method="POST" action="{{ route('plans.update', $plan) }}">
                @csrf @method('PUT')
                <div class="flex items-center" style="justify-content:space-between;margin-bottom:10px">
                    <span class="badge {{ $plan->is_active ? 'badge-green' : 'badge-gray' }} badge-dot">{{ $plan->is_active ? 'مفعّلة' : 'معطّلة' }}</span>
                    <span style="color:#64748b;font-size:12px">{{ $plan->slug }}</span>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                    {!! $field('الاسم', 'name', 'text', $plan->name, 'required') !!}
                    {!! $field('السعر', 'price', 'number', $plan->price, 'step=0.01 required') !!}
                    {!! $field('العملة', 'currency', 'text', $plan->currency, 'maxlength=3') !!}
                    <label style="display:block;font-size:12px;color:#64748b">نوع الدورة
                        <select name="billing_period" class="form-control" style="margin-top:3px">
                            @foreach($periods as $k => $v)<option value="{{ $k }}" @selected($plan->billing_period === $k)>{{ $v }}</option>@endforeach
                        </select>
                    </label>
                    {!! $field('المدة (أيام)', 'duration_days', 'number', $plan->duration_days, 'required') !!}
                    {!! $field('الترتيب', 'sort', 'number', $plan->sort) !!}
                </div>
                <label style="display:block;font-size:12px;color:#64748b;margin-top:10px">المزايا (سطر لكل ميزة)
                    <textarea name="features" rows="3" class="form-control" style="margin-top:3px">{{ implode("\n", $plan->features ?? []) }}</textarea>
                </label>
                <label class="switch" style="margin-top:10px"><input type="checkbox" name="is_active" value="1" @checked($plan->is_active)><span class="track"></span><span class="text-sm">مفعّلة</span></label>
                <div class="flex" style="gap:8px;margin-top:12px">
                    <button class="btn btn-primary" style="padding:6px 14px;font-size:13px"><i class="fa-solid fa-floppy-disk"></i> حفظ</button>
                </div>
            </form>
            <form method="POST" action="{{ route('plans.toggle', $plan) }}" style="margin-top:8px">
                @csrf
                <button class="btn" style="padding:6px 14px;font-size:13px;border:1px solid var(--border,#e2e8f0)">
                    {{ $plan->is_active ? 'تعطيل' : 'تفعيل' }}
                </button>
            </form>
        </div>
    @empty
        <div class="card" style="padding:24px"><x-empty icon="fa-box" title="لا توجد باقات بعد" /></div>
    @endforelse
</div>
@endsection
