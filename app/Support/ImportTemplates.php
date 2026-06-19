<?php

namespace App\Support;

/**
 * Single source of truth for import template columns + example rows.
 * Used by the in-app "download template" endpoints and the generated .xlsx samples.
 * Heading keys MUST match what the importers read via $row->get('...').
 */
class ImportTemplates
{
    public const SPARE_PARTS = [
        'title' => 'قطع الغيار',
        'headings' => ['name', 'part_number', 'category', 'quantity', 'min_stock', 'max_stock', 'unit_price'],
        'rows' => [
            ['فلتر مكيف سبليت', 'SP-1001', 'فلاتر', 20, 5, 50, 45],
            ['خرطوشة حبر أسود', 'SP-1002', 'أحبار', 12, 4, 30, 120],
            ['كابل شبكة CAT6', 'SP-1003', 'كهربائيات', 60, 15, 200, 8],
        ],
    ];

    public const ITEMS = [
        'title' => 'الأصناف',
        'headings' => ['name', 'code', 'category', 'unit', 'location', 'quantity', 'price'],
        'rows' => [
            ['قاطع كهربائي 32A', 'ITM-1001', 'مواد كهربائية', 'قطعة', 'المستودع - رف A', 40, 35],
            ['شريط لاصق عازل', 'ITM-1002', 'مستهلكات', 'لفة', 'المستودع - رف C', 150, 6],
            ['مفك براغي متعدد', 'ITM-1003', 'أدوات يدوية', 'قطعة', 'المستودع - رف B', 25, 22],
        ],
    ];

    /** All templates keyed by slug. */
    public static function all(): array
    {
        return [
            'spare_parts' => self::SPARE_PARTS,
            'items' => self::ITEMS,
        ];
    }
}
