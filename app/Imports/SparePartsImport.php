<?php

namespace App\Imports;

use App\Models\SparePart;
use App\Models\SpareCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Imports spare parts from an xlsx/csv with heading row:
 * name | part_number | category | quantity | min_stock | max_stock | unit_price
 * Rows are upserted by (company_id, part_number) so re-imports update stock.
 */
class SparePartsImport implements ToCollection, WithHeadingRow
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
                $categoryId = SpareCategory::firstOrCreate(
                    ['company_id' => $this->companyId, 'name' => $cat]
                )->id;
            }

            $partNumber = trim((string) $row->get('part_number')) ?: ('SP-' . strtoupper(Str::random(6)));
            $maxStock = $row->get('max_stock');
            $unitPrice = $row->get('unit_price');

            SparePart::updateOrCreate(
                ['company_id' => $this->companyId, 'part_number' => $partNumber],
                [
                    'name' => $name,
                    'category_id' => $categoryId,
                    'quantity' => (int) $row->get('quantity', 0),
                    'min_stock' => (int) $row->get('min_stock', 0),
                    'max_stock' => ($maxStock === null || $maxStock === '') ? null : (int) $maxStock,
                    'unit_price' => ($unitPrice === null || $unitPrice === '') ? null : (float) $unitPrice,
                ]
            );
            $this->imported++;
        }
    }
}
