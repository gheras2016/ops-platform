<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Ticket::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'department_id' => ['required', 'exists:departments,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'location_detail' => ['nullable', 'string', 'max:255'],
            'priority_id' => ['nullable', 'exists:priorities,id'],
            'asset_id' => ['nullable', 'exists:assets,id'],
            // due_at is intentionally NOT accepted here: the requester cannot estimate
            // the completion date. It is set later by the department head at assignment.
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'عنوان المشكلة',
            'department_id' => 'القسم',
            'priority_id' => 'الأولوية',
            'location_id' => 'الموقع',
        ];
    }
}
