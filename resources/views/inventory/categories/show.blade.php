@extends('layouts.app')
@section('title', $category->name)
@section('page-title', $category->name)
@section('content')
<div class="breadcrumb"><a href="{{ route('inventory.categories.index') }}">الفئات</a> <i class="fa-solid fa-chevron-left"></i> <span>{{ $category->name }}</span></div>
<div class="card">
    <div class="card-header"><h3>{{ $category->name }}</h3><span class="sub">{{ $category->items->count() }} صنف</span></div>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>الصنف</th><th>الكمية</th></tr></thead>
        <tbody>@forelse($category->items as $it)<tr><td class="cell-title">{{ $it->name }}</td><td>{{ $it->quantity }}</td></tr>@empty<tr><td colspan="2"><x-empty icon="fa-box" title="لا توجد أصناف" /></td></tr>@endforelse</tbody>
    </table></div>
</div>
@endsection
