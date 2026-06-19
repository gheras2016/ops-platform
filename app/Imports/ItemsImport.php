<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Imports inventory items from an xlsx/csv with heading row:
 * name | code | category | unit | location | quantity | price
 */
class ItemsImport implements ToCollection, WithHeadingRow
{
    public int $imported = 0;

    public function __construct(public int $companyId)
    {
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $name = trim((string) $row->get('name'));
            if ($name === '') {
                continue;
            }

            $categoryId = null;
            if ($cat = trim((string) $row->get('category'))) {
                $categoryId = Category::firstOrCreate(
                    ['company_id' => $this->companyId, 'name' => $cat],
                    ['status' => 'active']
                )->id;
            }

            $price = $row->get('price');
            $attributes = [
                'name' => $name,
                'category_id' => $categoryId,
                'unit' => trim((string) $row->get('unit')) ?: null,
                'location' => trim((string) $row->get('location')) ?: null,
                'quantity' => (int) $row->get('quantity', 0),
                'price' => ($price === null || $price === '') ? 0 : (float) $price,
                'status' => 'active',
            ];

            $code = trim((string) $row->get('code'));
            if ($code) {
                Item::updateOrCreate(['company_id' => $this->companyId, 'code' => $code], $attributes);
            } else {
                Item::create(array_merge(['company_id' => $this->companyId], $attributes));
            }
            $this->imported++;
        }
    }
}
