<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Console;

use Illuminate\Console\Command;
use ImranDev\UnicodePdf\Fonts\FontMetadata;
use ImranDev\UnicodePdf\UnicodePdfManager;

class FontListCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'unicode-pdf:font:list';

    /**
     * The console command description.
     */
    protected $description = 'List all registered custom font families and their details in a table';

    public function handle(UnicodePdfManager $manager): int
    {
        $fonts = $manager->getFontManager()->all();

        if (empty($fonts)) {
            $this->warn('No custom fonts are currently registered.');
            $this->line('You can register fonts in <fg=cyan>config/unicode-pdf.php</> or via <fg=cyan>UnicodePdf::registerFont()</>.');

            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($fonts as $family => $def) {
            $path = $def['regular'] ?? $def['bold'] ?? '';
            $format = 'TTF/OTF';
            $numGlyphs = 'N/A';
            $scripts = ! empty($def['scripts']) ? implode(', ', (array) $def['scripts']) : 'All';

            if (! empty($path) && file_exists($path)) {
                try {
                    $meta = FontMetadata::parse($path);
                    $format = $meta['format'];
                    $numGlyphs = (string) $meta['num_glyphs'];
                    $scripts = implode(', ', array_slice($meta['supported_scripts'], 0, 4));
                } catch (\Throwable) {
                    // Ignore parse errors
                }
            }

            $rows[] = [
                $family,
                $format,
                $numGlyphs,
                $scripts,
                $path ? basename($path) : 'None',
            ];
        }

        $this->table(
            ['Font Family', 'Format', 'Glyphs', 'Detected Scripts', 'Regular File'],
            $rows
        );

        return Command::SUCCESS;
    }
}
