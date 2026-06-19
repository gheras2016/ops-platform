<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => 'sometimes|string|max:255',
            'sku'           => 'sometimes|string|max:255|unique:inventory_items,sku,' . $this->inventory_item->id,
            'category_id'   => 'sometimes|exists:inventory_categories,id',
            'location_id'   => 'sometimes|exists:locations,id',
            'quantity'      => 'sometimes|numeric|min:0',
            'min_quantity'  => 'sometimes|numeric|min:0',
        ];
    }
}
