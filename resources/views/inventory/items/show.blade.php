@extends('layouts.app')
@section('title', $item->name)
@section('page-title', $item->name)
@section('content')
<div class="breadcrumb"><a href="{{ route('inventory.items.index') }}">الأصناف</a> <i class="fa-solid fa-chevron-left"></i> <span>{{ $item->name }}</span></div>
<div class="card card-body" style="max-width:520px">
    <div class="kv"><span class="k">الرمز</span><span class="v">{{ $item->code ?? '—' }}</span></div>
    <div class="kv"><span class="k">الفئة</span><span class="v">{{ $item->category?->name ?? '—' }}</span></div>
    <div class="kv"><span class="k">الكمية</span><span class="v">{{ $item->quantity }} {{ $item->unit }}</span></div>
    <div class="kv"><span class="k">الموقع</span><span class="v">{{ $item->location ?? '—' }}</span></div>
    <a href="{{ route('inventory.items.edit', $item) }}" class="btn btn-light btn-block mt-4"><i class="fa-solid fa-pen"></i> تعديل</a>
</div>
@endsection
