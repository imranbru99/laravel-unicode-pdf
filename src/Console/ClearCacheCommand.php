<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Console;

use Illuminate\Console\Command;
use ImranDev\UnicodePdf\UnicodePdfManager;

class ClearCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'unicode-pdf:clear-cache';

    /**
     * The console command description.
     */
    protected $description = 'Clear cached font metadata, glyph tables, and temporary PDF files';

    public function handle(UnicodePdfManager $manager): int
    {
        $manager->clearCache();

        $this->info('Unicode PDF cache cleared successfully.');

        return Command::SUCCESS;
    }
}
