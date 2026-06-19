<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => 'required|string|max:255',
            'sku'           => 'required|string|max:255|unique:inventory_items,sku',
            'category_id'   => 'required|exists:inventory_categories,id',
            'location_id'   => 'required|exists:locations,id',
            'quantity'      => 'required|numeric|min:0',
            'min_quantity'  => 'required|numeric|min:0',
        ];
    }
}

