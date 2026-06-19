<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMaintenanceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'         => 'sometimes|string|max:255',
            'description'   => 'nullable|string',
            'priority_id'   => 'sometimes|exists:priorities,id',
            'status_id'     => 'sometimes|exists:ticket_statuses,id',
            'asset_id'      => 'sometimes|exists:assets,id',
            'user_id'       => 'sometimes|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.string'          => 'العنوان يجب أن يكون نص.',
            'priority_id.exists'    => 'الأولوية غير موجودة.',
            'status_id.exists'      => 'الحالة غير موجودة.',
            'asset_id.exists'       => 'الأصل غير موجود.',
            'user_id.exists'        => 'المستخدم غير موجود.',
        ];
    }
}
