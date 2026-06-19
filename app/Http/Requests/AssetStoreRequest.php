<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssetStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name'            => 'required|string|max:255',
            'code'            => 'required|string|max:255|unique:assets,code',
            'category_id'     => 'required|integer|exists:asset_categories,id',
            'location_id'     => 'required|integer|exists:locations,id',
            'department_id'   => 'required|integer|exists:departments,id',
            'serial_number'   => 'nullable|string|max:255',
            'brand'           => 'nullable|string|max:255',
            'model'           => 'nullable|string|max:255',
            'installation_date' => 'nullable|date',
            'warranty_expiry'   => 'nullable|date',
        ];
    }
}
