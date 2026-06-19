<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LocationUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name'        => 'sometimes|string|max:255|unique:locations,name,' . $this->location->id,
            'description' => 'nullable|string',
        ];
    }
}
