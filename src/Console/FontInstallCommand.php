<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Console;

use Illuminate\Console\Command;
use ImranDev\UnicodePdf\Native\FontLibrary;
use ImranDev\UnicodePdf\UnicodePdfManager;

class FontInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'unicode-pdf:font:install
                            {--font= : Font preset (bengali, arabic, devanagari, tamil, thai, hebrew, khmer, myanmar, ethiopic, universal)}
                            {--force : Re-download even if the file already exists}';

    /**
     * The console command description.
     */
    protected $description = 'Download SIL OFL Noto fonts into the package font directory';

    /**
     * Open-source Google Noto font information (SIL Open Font License).
     *
     * @var array<string, array{name: string, url: string, file: string, description: string}>
     */
    protected static array $openFonts = [
        'bengali' => [
            'name' => 'Noto Sans Bengali',
            'url' => 'https://github.com/googlefonts/noto-fonts/raw/main/hinted/ttf/NotoSansBengali/NotoSansBengali-Regular.ttf',
            'file' => 'NotoSansBengali-Regular.ttf',
            'description' => 'Google Noto Sans Bengali (SIL Open Font License)',
        ],
        'arabic' => [
            'name' => 'Noto Sans Arabic',
            'url' => 'https://github.com/googlefonts/noto-fonts/raw/main/hinted/ttf/NotoSansArabic/NotoSansArabic-Regular.ttf',
            'file' => 'NotoSansArabic-Regular.ttf',
            'description' => 'Google Noto Sans Arabic (SIL Open Font License)',
        ],
        'devanagari' => [
            'name' => 'Noto Sans Devanagari',
            'url' => 'https://github.com/googlefonts/noto-fonts/raw/main/hinted/ttf/NotoSansDevanagari/NotoSansDevanagari-Regular.ttf',
            'file' => 'NotoSansDevanagari-Regular.ttf',
            'description' => 'Google Noto Sans Devanagari for Hindi/Marathi/Nepali (SIL Open Font License)',
        ],
        'tamil' => [
            'name' => 'Noto Sans Tamil',
            'url' => 'https://github.com/googlefonts/noto-fonts/raw/main/hinted/ttf/NotoSansTamil/NotoSansTamil-Regular.ttf',
            'file' => 'NotoSansTamil-Regular.ttf',
            'description' => 'Google Noto Sans Tamil (SIL Open Font License)',
        ],
        'thai' => [
            'name' => 'Noto Sans Thai',
            'url' => 'https://github.com/googlefonts/noto-fonts/raw/main/hinted/ttf/NotoSansThai/NotoSansThai-Regular.ttf',
            'file' => 'NotoSansThai-Regular.ttf',
            'description' => 'Google Noto Sans Thai (SIL Open Font License)',
        ],
        'hebrew' => [
            'name' => 'Noto Sans Hebrew',
            'url' => 'https://github.com/googlefonts/noto-fonts/raw/main/hinted/ttf/NotoSansHebrew/NotoSansHebrew-Regular.ttf',
            'file' => 'NotoSansHebrew-Regular.ttf',
            'description' => 'Google Noto Sans Hebrew (SIL Open Font License)',
        ],
        'khmer' => [
            'name' => 'Noto Sans Khmer',
            'url' => 'https://github.com/googlefonts/noto-fonts/raw/main/hinted/ttf/NotoSansKhmer/NotoSansKhmer-Regular.ttf',
            'file' => 'NotoSansKhmer-Regular.ttf',
            'description' => 'Google Noto Sans Khmer (SIL Open Font License)',
        ],
        'myanmar' => [
            'name' => 'Noto Sans Myanmar',
            'url' => 'https://github.com/googlefonts/noto-fonts/raw/main/hinted/ttf/NotoSansMyanmar/NotoSansMyanmar-Regular.ttf',
            'file' => 'NotoSansMyanmar-Regular.ttf',
            'description' => 'Google Noto Sans Myanmar (SIL Open Font License)',
        ],
        'ethiopic' => [
            'name' => 'Noto Sans Ethiopic',
            'url' => 'https://github.com/googlefonts/noto-fonts/raw/main/hinted/ttf/NotoSansEthiopic/NotoSansEthiopic-Regular.ttf',
            'file' => 'NotoSansEthiopic-Regular.ttf',
            'description' => 'Google Noto Sans Ethiopic (SIL Open Font License)',
        ],
        'universal' => [
            'name' => 'Noto Sans',
            'url' => 'https://github.com/googlefonts/noto-fonts/raw/main/hinted/ttf/NotoSans/NotoSans-Regular.ttf',
            'file' => 'NotoSans-Regular.ttf',
            'description' => 'Google Noto Sans base font (SIL Open Font License)',
        ],
    ];

    public function handle(UnicodePdfManager $manager): int
    {
        $fontOption = strtolower((string) ($this->option('font') ?: ''));

        if ($fontOption === '') {
            $fontOption = $this->choice(
                'Which font preset would you like to install?',
                array_keys(self::$openFonts),
                0
            );
        }

        $fontInfo = self::$openFonts[$fontOption] ?? null;
        if (! $fontInfo) {
            $this->error("Unknown font preset: {$fontOption}. Available presets: ".implode(', ', array_keys(self::$openFonts)));

            return Command::FAILURE;
        }

        $storageDir = config('unicode-pdf.font_path', storage_path('app/unicode-pdf/fonts'));
        if (! is_string($storageDir) || $storageDir === '') {
            $storageDir = FontLibrary::packageFontPath();
        }
        if (! is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }

        $destination = rtrim($storageDir, '/\\').DIRECTORY_SEPARATOR.$fontInfo['file'];

        $this->info("Font Preset: <fg=cyan>{$fontInfo['name']}</>");
        $this->line("Description: {$fontInfo['description']}");
        $this->line("Destination: <fg=yellow>{$destination}</>");
        $this->newLine();

        if (is_file($destination) && ! $this->option('force')) {
            $this->registerDownloadedFont($manager, $fontInfo['name'], $destination);
            $this->info('Font already installed. Use --force to download again.');

            return Command::SUCCESS;
        }

        $this->line("Downloading <fg=cyan>{$fontInfo['url']}</> ...");
        $binary = $this->download($fontInfo['url']);

        if ($binary === null || strlen($binary) < 1000) {
            $this->warn('Automatic download failed. Place the TTF manually:');
            $this->line("  1. {$fontInfo['url']}");
            $this->line("  2. Save as {$destination}");
            $this->line("  3. UnicodePdf::registerFont(['family' => '{$fontInfo['name']}', 'regular' => '{$destination}']);");

            return Command::SUCCESS;
        }

        if (file_put_contents($destination, $binary) === false) {
            $this->error("Unable to write font file to {$destination}");

            return Command::FAILURE;
        }

        $this->registerDownloadedFont($manager, $fontInfo['name'], $destination);
        $this->info("Installed {$fontInfo['name']} (".number_format(strlen($binary) / 1024, 1).' KB).');

        return Command::SUCCESS;
    }

    protected function download(string $url): ?string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 60,
                'follow_location' => 1,
                'header' => "User-Agent: laravel-unicode-pdf\r\n",
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $data = @file_get_contents($url, false, $context);
        if (is_string($data) && $data !== '') {
            return $data;
        }

        if (function_exists('curl_init')) {
            $handle = curl_init($url);
            curl_setopt_array($handle, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_USERAGENT => 'laravel-unicode-pdf',
            ]);
            $data = curl_exec($handle);
            curl_close($handle);

            if (is_string($data) && $data !== '') {
                return $data;
            }
        }

        return null;
    }

    protected function registerDownloadedFont(UnicodePdfManager $manager, string $family, string $path): void
    {
        $manager->registerFont([
            'family' => $family,
            'regular' => $path,
        ]);
    }
}
