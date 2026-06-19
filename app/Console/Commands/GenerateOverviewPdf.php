<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Renders the marketing "System Overview" view to a polished Arabic (RTL) PDF
 * via mPDF — the same engine used for ticket/purchase PDFs.
 */
class GenerateOverviewPdf extends Command
{
    protected $signature = 'docs:overview {--o= : Output path (defaults to storage/app/docs/ops-platform-overview.pdf)}';

    protected $description = 'Generate the professional System Overview (proposal) PDF.';

    public function handle(): int
    {
        $out = $this->option('o') ?: storage_path('app/docs/ops-platform-overview.pdf');
        $dir = dirname($out);
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $tempDir = storage_path('app/mpdf');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => $tempDir,
            'default_font' => 'dejavusans',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'margin_top' => 0,
            'margin_bottom' => 0,
            'margin_left' => 0,
            'margin_right' => 0,
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->SetTitle('OPS Platform — System Overview');
        $mpdf->WriteHTML(view('docs.overview')->render());
        $mpdf->Output($out, \Mpdf\Output\Destination::FILE);

        $this->info('تم إنشاء ملف العرض التعريفي:');
        $this->line($out);

        return self::SUCCESS;
    }
}
