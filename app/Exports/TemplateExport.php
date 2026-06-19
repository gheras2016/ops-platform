<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * A reusable, styled import template (.xlsx): an English heading row that the
 * importers map on, followed by example rows users can replace.
 */
class TemplateExport implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize, WithEvents
{
    public function __construct(
        public array $headings,
        public array $rows,
        public string $title,
    ) {
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->setRightToLeft(true);

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4F46E5']],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastCol = $sheet->getHighestColumn();
                $sheet->getRowDimension(1)->setRowHeight(26);
                // Freeze the heading row and add a light border to example rows.
                $sheet->freezePane('A2');
                $rowCount = count($this->rows) + 1;
                $sheet->getStyle("A1:{$lastCol}{$rowCount}")
                    ->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
                    ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFE2E8F0'));
            },
        ];
    }
}
