<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('ticket'));
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'department_id' => ['required', 'exists:departments,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'priority_id' => ['nullable', 'exists:priorities,id'],
            'asset_id' => ['nullable', 'exists:assets,id'],
            'due_at' => ['nullable', 'date'],
        ];
    }
}
