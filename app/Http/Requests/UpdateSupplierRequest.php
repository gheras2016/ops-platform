<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'    => 'sometimes|string|max:255',
            'email'   => 'sometimes|email|max:255|unique:suppliers,email,' . $this->supplier->id,
            'phone'   => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ];
    }
}
