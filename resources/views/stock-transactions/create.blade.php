@extends('layouts.app')
@section('title', 'حركة مخزون جديدة')
@section('page-title', 'حركة مخزون جديدة')
@section('content')
<div class="breadcrumb"><a href="{{ route('stock-transactions.index') }}">حركات المخزون</a> <i class="fa-solid fa-chevron-left"></i> <span>جديدة</span></div>
<form action="{{ route('stock-transactions.store') }}" method="POST" class="card card-body" style="max-width:620px">@csrf
    <div class="form-grid">
        <div class="form-group full"><label class="form-label">قطعة الغيار <span class="req">*</span></label>
            <select name="spare_part_id" class="form-select" required><option value="">— اختر —</option>@foreach($spareParts as $p)<option value="{{ $p->id }}">{{ $p->name }} ({{ $p->quantity }})</option>@endforeach</select>
        </div>
        <div class="form-group"><label class="form-label">النوع <span class="req">*</span></label>
            <select name="type" class="form-select" required><option value="in">إدخال</option><option value="out">إخراج</option></select>
        </div>
        <div class="form-group"><label class="form-label">الكمية <span class="req">*</span></label><input type="number" name="quantity" class="form-control" min="1" value="1" required></div>
    </div>
    <div class="flex gap-2 mt-5"><button class="btn btn-primary"><i class="fa-solid fa-save"></i> تسجيل</button><a href="{{ route('stock-transactions.index') }}" class="btn btn-light">إلغاء</a></div>
</form>
@endsection
