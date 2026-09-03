<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Console;

use Dompdf\Dompdf;
use Illuminate\Console\Command;
use ImranDev\UnicodePdf\Engines\NativeEngine;
use ImranDev\UnicodePdf\UnicodePdfManager;
use Mpdf\Mpdf;
use Spatie\Browsershot\Browsershot;
use TCPDF;

class DiagnoseCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'unicode-pdf:diagnose';

    /**
     * The console command description.
     */
    protected $description = 'Perform comprehensive system, engine, font, and Unicode diagnostics';

    public function handle(UnicodePdfManager $manager): int
    {
        $this->info('======================================================');
        $this->info('           Unicode PDF System Diagnostics            ');
        $this->info('======================================================');

        // 1. Environment
        $this->newLine();
        $this->line('<fg=yellow;options=bold>1. Environment & PHP Extensions:</>');
        $this->checkEnvironment();

        // 2. Engines
        $this->newLine();
        $this->line('<fg=yellow;options=bold>2. PDF Engines Status:</>');
        $this->checkEngines($manager);

        // 3. Fonts & Storage
        $this->newLine();
        $this->line('<fg=yellow;options=bold>3. Font System & Cache Directories:</>');
        $this->checkStorageAndFonts($manager);

        // 4. Unicode Capabilities
        $this->newLine();
        $this->line('<fg=yellow;options=bold>4. Unicode & Script Shaping Support:</>');
        $this->checkCapabilities($manager);

        $this->newLine();
        $this->info('Diagnostics completed successfully.');

        return Command::SUCCESS;
    }

    protected function checkEnvironment(): void
    {
        $phpVersion = PHP_VERSION;
        $this->line("  PHP Version: {$phpVersion} ".(version_compare($phpVersion, '8.1.0', '>=') ? '<fg=green>✓ OK</>' : '<fg=red>✗ PHP 8.1+ required</>'));

        $extensions = [
            'mbstring' => 'Required for multibyte UTF-8 string manipulation',
            'json' => 'Required for font metadata caching and serialization',
            'intl' => 'Recommended for Unicode normalization and ICU script classification',
            'gd' => 'Recommended for image rendering and thumbnail generation',
            'iconv' => 'Recommended for legacy font encoding conversion',
        ];

        foreach ($extensions as $ext => $purpose) {
            $loaded = extension_loaded($ext);
            $status = $loaded ? '<fg=green>✓ Loaded</>' : ($ext === 'intl' || $ext === 'gd' || $ext === 'iconv' ? '<fg=yellow>! Missing (Optional)</>' : '<fg=red>✗ Missing (Required)</>');
            $this->line("  - {$ext}: {$status} <fg=gray>({$purpose})</>");
        }
    }

    protected function checkEngines(UnicodePdfManager $manager): void
    {
        $defaultEngine = $manager->getDefaultEngine();
        $this->line("  Default Configured Engine: <fg=cyan;options=bold>{$defaultEngine}</>");

        $drivers = [
            'native' => [
                'class' => NativeEngine::class,
                'pkg' => null,
                'notes' => 'Built-in engine — no extra Composer packages. TTF embedding, RTL, Bengali/Arabic shaping',
            ],
            'dompdf' => [
                'class' => Dompdf::class,
                'pkg' => 'dompdf/dompdf',
                'notes' => 'Optional. Lightweight HTML/CSS, basic Unicode with TTF embedding',
            ],
            'mpdf' => [
                'class' => Mpdf::class,
                'pkg' => 'mpdf/mpdf',
                'notes' => 'Advanced multilingual support, native RTL, Bengali/Devanagari/Arabic shaping',
            ],
            'tcpdf' => [
                'class' => TCPDF::class,
                'pkg' => 'tecnickcom/tcpdf',
                'notes' => 'Fast, RTL support, standard Unicode font embedding',
            ],
            'browsershot' => [
                'class' => Browsershot::class,
                'pkg' => 'spatie/browsershot',
                'notes' => 'Headless Chromium, pixel-perfect CSS Grid/Flexbox, full OpenType & HarfBuzz',
            ],
        ];

        foreach ($drivers as $name => $info) {
            $installed = $info['pkg'] === null || class_exists($info['class']);
            $status = $installed
                ? '<fg=green>✓ Ready</>'
                : '<fg=gray>- Optional (composer require '.$info['pkg'].')</>';
            $activeTag = $name === $defaultEngine ? ' <fg=cyan>[ACTIVE]</>' : '';
            $this->line("  - {$name}: {$status}{$activeTag}");
            $this->line("    <fg=gray>{$info['notes']}</>");
        }
    }

    protected function checkStorageAndFonts(UnicodePdfManager $manager): void
    {
        $fontPath = config('unicode-pdf.font_path', storage_path('app/unicode-pdf/fonts'));
        $cachePath = config('unicode-pdf.font_cache', storage_path('app/unicode-pdf/cache/fonts'));

        $this->line("  Font Directory: {$fontPath} ".(is_dir($fontPath) ? '<fg=green>✓ Exists</>' : '<fg=yellow>! Not created yet</>'));
        $this->line("  Font Cache: {$cachePath} ".(is_writable(dirname($cachePath)) || is_writable($cachePath) ? '<fg=green>✓ Writable</>' : '<fg=red>✗ Check permissions</>'));

        $fonts = $manager->getFontManager()->all();
        $this->line('  Registered Custom Fonts: '.(count($fonts) > 0 ? count($fonts).' font families' : '<fg=gray>None (using system/standard fallbacks)</>'));

        $fallbacks = (array) config('unicode-pdf.fallback_fonts', []);
        $this->line('  Configured Fallback Stack: <fg=gray>'.implode(', ', array_slice($fallbacks, 0, 5)).(count($fallbacks) > 5 ? '... ('.count($fallbacks).' total)' : '').'</>');
    }

    protected function checkCapabilities(UnicodePdfManager $manager): void
    {
        $capabilities = [
            'unicode' => 'Universal Unicode UTF-8 handling',
            'rtl' => 'Right-to-Left script layout (Arabic, Hebrew, Persian, Urdu)',
            'font-shaping' => 'Complex script glyph shaping (Bengali, Devanagari, Arabic conjuncts)',
            'svg' => 'Vector graphics (SVG) support',
            'encryption' => 'PDF document password protection & permissions',
        ];

        foreach ($capabilities as $cap => $desc) {
            $supported = $manager->supports($cap);
            $status = $supported ? '<fg=green>✓ Supported</>' : '<fg=yellow>! Limited by current engine</>';
            $this->line("  - {$cap}: {$status} <fg=gray>({$desc})</>");
        }
    }
}
