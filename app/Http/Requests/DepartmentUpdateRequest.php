<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepartmentUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name'        => 'sometimes|string|max:255|unique:departments,name,' . $this->department->id,
            'description' => 'nullable|string',
        ];
    }
}
