<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoleUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name'        => 'sometimes|string|unique:roles,name,' . $this->role->id,
            'permissions' => 'nullable|array',
        ];
    }
}
