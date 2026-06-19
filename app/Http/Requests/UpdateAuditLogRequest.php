<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAuditLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'           => 'sometimes|exists:users,id',
            'action'            => 'sometimes|string|in:create,update,delete,view',
            'auditable_type'    => 'sometimes|string|max:255',
            'auditable_id'      => 'sometimes|integer|min:1',
            'old_values'        => 'nullable|array',
            'new_values'        => 'nullable|array',
            'ip_address'        => 'nullable|ip',
            'user_agent'        => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.exists'         => 'المستخدم غير موجود.',
            'action.in'              => 'العملية غير صحيحة.',
            'auditable_type.string'  => 'نوع الكائن يجب أن يكون نص.',
            'auditable_id.integer'   => 'معرّف الكائن يجب أن يكون رقم.',
        ];
    }
}
