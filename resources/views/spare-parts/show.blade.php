@extends('layouts.app')
@section('title', $sparePart->name)
@section('page-title', $sparePart->name)
@section('content')
<div class="breadcrumb"><a href="{{ route('spare-parts.index') }}">قطع الغيار</a> <i class="fa-solid fa-chevron-left"></i> <span>{{ $sparePart->name }}</span></div>
<div class="card card-body" style="max-width:560px">
    <div class="kv"><span class="k">الرقم</span><span class="v">{{ $sparePart->part_number }}</span></div>
    <div class="kv"><span class="k">الفئة</span><span class="v">{{ $sparePart->category?->name ?? '—' }}</span></div>
    <div class="kv"><span class="k">الكمية</span><span class="v">{{ $sparePart->quantity }}</span></div>
    <div class="kv"><span class="k">الحد الأدنى</span><span class="v">{{ $sparePart->min_stock }}</span></div>
    <div class="kv"><span class="k">سعر الوحدة</span><span class="v">{{ $sparePart->unit_price ?? '—' }}</span></div>
    <a href="{{ route('spare-parts.edit', $sparePart) }}" class="btn btn-light btn-block mt-4"><i class="fa-solid fa-pen"></i> تعديل</a>
</div>
@endsection
