@extends('layouts.app')
@section('title', 'تعديل التذكرة')
@section('page-title', 'تعديل التذكرة')
@section('page-sub', $ticket->ticket_number)

@section('content')
<div class="breadcrumb"><a href="{{ route('tickets.index') }}">التذاكر</a> <i class="fa-solid fa-chevron-left"></i> <a href="{{ route('tickets.show', $ticket) }}">{{ $ticket->ticket_number }}</a> <i class="fa-solid fa-chevron-left"></i> <span>تعديل</span></div>

<form action="{{ route('tickets.update', $ticket) }}" method="POST" class="card card-body" style="max-width:760px">
    @csrf @method('PUT')
    <div class="form-grid">
        <div class="form-group full">
            <label class="form-label">عنوان المشكلة <span class="req">*</span></label>
            <input type="text" name="title" class="form-control" value="{{ old('title', $ticket->title) }}" required>
        </div>
        <div class="form-group full">
            <label class="form-label">الوصف</label>
            <textarea name="description" class="form-control">{{ old('description', $ticket->description) }}</textarea>
        </div>
        <div class="form-group">
            <label class="form-label">القسم <span class="req">*</span></label>
            <select name="department_id" class="form-select" required>
                @foreach($departments as $d)<option value="{{ $d->id }}" @selected(old('department_id', $ticket->department_id) == $d->id)>{{ $d->name }}</option>@endforeach
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">الأولوية</label>
            <select name="priority_id" class="form-select">
                <option value="">—</option>
                @foreach($priorities as $p)<option value="{{ $p->id }}" @selected(old('priority_id', $ticket->priority_id) == $p->id)>{{ $p->name }}</option>@endforeach
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">الموقع</label>
            <select name="location_id" class="form-select">
                <option value="">—</option>
                @foreach($locations as $l)<option value="{{ $l->id }}" @selected(old('location_id', $ticket->location_id) == $l->id)>{{ $l->name }}</option>@endforeach
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">الموعد النهائي</label>
            <input type="date" name="due_at" class="form-control" value="{{ old('due_at', $ticket->due_at?->format('Y-m-d')) }}">
        </div>
    </div>
    <div class="flex gap-2 mt-5">
        <button class="btn btn-primary"><i class="fa-solid fa-save"></i> حفظ التعديلات</button>
        <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-light">إلغاء</a>
    </div>
</form>
@endsection
