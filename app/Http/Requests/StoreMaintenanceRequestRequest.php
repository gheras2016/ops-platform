<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // اسمح لكل المستخدمين
    }

    public function rules(): array
    {
        return [
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'priority_id'   => 'required|exists:priorities,id',
            'status_id'     => 'required|exists:ticket_statuses,id',
            'asset_id'      => 'required|exists:assets,id',
            'user_id'       => 'required|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'        => 'العنوان مطلوب.',
            'priority_id.required'  => 'الأولوية مطلوبة.',
            'status_id.required'    => 'الحالة مطلوبة.',
            'asset_id.required'     => 'الأصل مطلوب.',
            'user_id.required'      => 'المستخدم مطلوب.',
        ];
    }
}
