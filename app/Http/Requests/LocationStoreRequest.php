<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LocationStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name'        => 'required|string|max:255|unique:locations,name',
            'description' => 'nullable|string',
        ];
    }
}
