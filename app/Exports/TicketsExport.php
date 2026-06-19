<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TicketsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    public function __construct(protected Collection $tickets)
    {
    }

    public function collection(): Collection
    {
        return $this->tickets;
    }

    public function title(): string
    {
        return 'التذاكر';
    }

    public function headings(): array
    {
        return ['رقم التذكرة', 'العنوان', 'القسم', 'الأولوية', 'الحالة', 'مقدم الطلب', 'الفني', 'الإنجاز %', 'تاريخ الإنشاء', 'تاريخ الإغلاق'];
    }

    /** @param \App\Models\Ticket $t */
    public function map($t): array
    {
        return [
            $t->ticket_number,
            $t->title,
            $t->department?->name,
            $t->priority?->name,
            $t->statusLabel(),
            $t->creator?->name,
            $t->technician?->name ?? '—',
            $t->progress,
            $t->created_at?->format('Y-m-d'),
            $t->closed_at?->format('Y-m-d') ?? '—',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->setRightToLeft(true);

        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4F46E5']]],
        ];
    }
}
