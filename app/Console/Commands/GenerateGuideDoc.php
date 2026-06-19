<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Renders the system guide / lifecycle view to a Word-openable .doc file
 * (HTML-based, RTL Arabic, editable in Microsoft Word). Screenshot
 * placeholders name the exact page + route to capture.
 */
class GenerateGuideDoc extends Command
{
    protected $signature = 'docs:guide {--o= : Output path (defaults to storage/app/docs/ops-platform-guide.doc)}';

    protected $description = 'Generate the detailed system guide / lifecycle as an editable Word (.doc) file.';

    public function handle(): int
    {
        $out = $this->option('o') ?: storage_path('app/docs/ops-platform-guide.doc');
        $dir = dirname($out);
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents($out, view('docs.guide')->render());

        $this->info('تم إنشاء دليل النظام (Word):');
        $this->line($out);

        return self::SUCCESS;
    }
}
