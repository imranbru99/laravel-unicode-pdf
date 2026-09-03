<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Console;

use Illuminate\Console\Command;
use ImranDev\UnicodePdf\UnicodePdfManager;

class FontsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'unicode-pdf:fonts';

    /**
     * The console command description.
     */
    protected $description = 'Display summary of default, registered, and fallback fonts with script coverage';

    public function handle(UnicodePdfManager $manager): int
    {
        $defaultFont = config('unicode-pdf.default_font', 'Noto Sans');
        $fallbackFonts = (array) config('unicode-pdf.fallback_fonts', []);
        $registeredFonts = $manager->getFontManager()->all();

        $this->info('======================================================');
        $this->info('             Unicode PDF Font Diagnostics             ');
        $this->info('======================================================');
        $this->newLine();

        $this->line("<fg=yellow;options=bold>Default Font:</>\n{$defaultFont}");
        $this->newLine();

        $this->line('<fg=yellow;options=bold>Registered Fonts:</>');
        if (empty($registeredFonts)) {
            $this->line('  <fg=gray>(No custom fonts registered; fallback chain will be used)</>');
        } else {
            foreach ($registeredFonts as $family => $def) {
                $styles = array_filter(['regular', 'bold', 'italic', 'bold_italic'], fn ($s) => ! empty($def[$s]));
                $stylesStr = implode(', ', $styles);
                $this->line("  <fg=green>✓</> <fg=cyan>{$family}</> <fg=gray>({$stylesStr})</>");
            }
        }

        $this->newLine();
        $this->line('<fg=yellow;options=bold>Configured Fallback Chain:</>');
        foreach ($fallbackFonts as $index => $font) {
            $num = $index + 1;
            $this->line("  {$num}. {$font}");
        }

        $this->newLine();
        $this->line('<fg=yellow;options=bold>Supported Script Presets:</>');
        $presets = ['universal', 'bengali', 'arabic', 'indian', 'cjk'];
        foreach ($presets as $presetName) {
            $preset = $manager->getFontManager()->getPreset($presetName);
            if ($preset) {
                $this->line("  <fg=green>✓</> <fg=cyan>{$presetName}</>: Default <fg=white>{$preset->getDefaultFont()}</> [Direction: <fg=magenta>{$preset->getDirection()}</>]");
            }
        }

        $this->newLine();

        return Command::SUCCESS;
    }
}
