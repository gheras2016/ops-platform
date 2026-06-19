<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAuditLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'           => 'required|exists:users,id',
            'action'            => 'required|string|in:create,update,delete,view',
            'auditable_type'    => 'required|string|max:255',
            'auditable_id'      => 'required|integer|min:1',
            'old_values'        => 'nullable|array',
            'new_values'        => 'nullable|array',
            'ip_address'        => 'nullable|ip',
            'user_agent'        => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required'       => 'المستخدم مطلوب.',
            'action.required'        => 'العملية مطلوبة.',
            'auditable_type.required' => 'نوع الكائن مطلوب.',
            'auditable_id.required'  => 'معرّف الكائن مطلوب.',
        ];
    }
}
