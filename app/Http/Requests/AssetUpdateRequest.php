<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssetUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name'            => 'sometimes|string|max:255',
            'code'            => 'sometimes|string|max:255|unique:assets,code,' . $this->asset->id,
            'category_id'     => 'sometimes|integer|exists:asset_categories,id',
            'location_id'     => 'sometimes|integer|exists:locations,id',
            'department_id'   => 'sometimes|integer|exists:departments,id',
            'serial_number'   => 'nullable|string|max:255',
            'brand'           => 'nullable|string|max:255',
            'model'           => 'nullable|string|max:255',
            'installation_date' => 'nullable|date',
            'warranty_expiry'   => 'nullable|date',
        ];
    }
}
