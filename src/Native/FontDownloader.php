<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Native;

class FontDownloader
{
    /**
     * @var array<string, array{family: string, file: string, urls: list<string>, min?: int}>
     */
    public const FONTS = [
        'universal' => [
            'family' => 'Noto Sans',
            'file' => 'NotoSans-Regular.ttf',
            'min' => 200000,
            'urls' => [
                'https://github.com/google/fonts/raw/main/ofl/notosans/NotoSans%5Bwdth%2Cwght%5D.ttf',
                'https://github.com/googlefonts/noto-fonts/raw/main/hinted/ttf/NotoSans/NotoSans-Regular.ttf',
                'https://cdn.jsdelivr.net/fontsource/fonts/noto-sans@5.2.5/latin-400-normal.ttf',
            ],
        ],
        'bengali' => [
            'family' => 'Noto Sans Bengali',
            'file' => 'NotoSansBengali-Regular.ttf',
            'min' => 140000,
            'urls' => [
                'https://github.com/googlefonts/noto-fonts/raw/main/hinted/ttf/NotoSansBengali/NotoSansBengali-Regular.ttf',
                'https://cdn.jsdelivr.net/fontsource/fonts/noto-sans-bengali@5.2.5/bengali-400-normal.ttf',
            ],
        ],
        'arabic' => [
            'family' => 'Noto Sans Arabic',
            'file' => 'NotoSansArabic-Regular.ttf',
            'min' => 140000,
            'urls' => [
                'https://github.com/googlefonts/noto-fonts/raw/main/hinted/ttf/NotoSansArabic/NotoSansArabic-Regular.ttf',
                'https://cdn.jsdelivr.net/fontsource/fonts/noto-sans-arabic@5.2.5/arabic-400-normal.ttf',
            ],
        ],
        'devanagari' => [
            'family' => 'Noto Sans Devanagari',
            'file' => 'NotoSansDevanagari-Regular.ttf',
            'min' => 140000,
            'urls' => [
                'https://github.com/googlefonts/noto-fonts/raw/main/hinted/ttf/NotoSansDevanagari/NotoSansDevanagari-Regular.ttf',
                'https://cdn.jsdelivr.net/fontsource/fonts/noto-sans-devanagari@5.2.5/devanagari-400-normal.ttf',
            ],
        ],
        'hebrew' => [
            'family' => 'Noto Sans Hebrew',
            'file' => 'NotoSansHebrew-Regular.ttf',
            'urls' => [
                'https://cdn.jsdelivr.net/fontsource/fonts/noto-sans-hebrew@5.2.5/hebrew-400-normal.ttf',
                'https://github.com/notofonts/hebrew/raw/main/fonts/NotoSansHebrew/unhinted/ttf/NotoSansHebrew-Regular.ttf',
                'https://github.com/googlefonts/noto-fonts/raw/main/hinted/ttf/NotoSansHebrew/NotoSansHebrew-Regular.ttf',
            ],
        ],
        'thai' => [
            'family' => 'Noto Sans Thai',
            'file' => 'NotoSansThai-Regular.ttf',
            'urls' => [
                'https://cdn.jsdelivr.net/gh/notofonts/notofonts.github.io/fonts/NotoSansThai/unhinted/ttf/NotoSansThai-Regular.ttf',
                'https://cdn.jsdelivr.net/fontsource/fonts/noto-sans-thai@5.2.5/thai-400-normal.ttf',
                'https://github.com/googlefonts/noto-fonts/raw/main/hinted/ttf/NotoSansThai/NotoSansThai-Regular.ttf',
            ],
        ],
    ];

    /**
     * @return list<string> Paths of fonts that are present after the attempt
     */
    public static function ensure(string $directory, ?callable $progress = null): array
    {
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $present = [];
        foreach (self::FONTS as $preset => $info) {
            $path = rtrim($directory, '/\\').DIRECTORY_SEPARATOR.$info['file'];
            $minimum = $info['min'] ?? 10000;
            if (is_readable($path) && filesize($path) >= $minimum) {
                $present[] = $path;

                continue;
            }

            $progress && $progress("Downloading {$info['family']}...");
            if (self::isTesting() && ! getenv('UNICODE_PDF_DOWNLOAD_FONTS')) {
                continue;
            }

            $binary = self::downloadBest($info['urls'], $minimum);
            if ($binary !== null) {
                file_put_contents($path, $binary);
                $present[] = $path;
                $progress && $progress('Saved '.$info['file'].' ('.number_format(strlen($binary)).' bytes)');
            }
        }

        return $present;
    }

    /**
     * @param  list<string>  $urls
     */
    public static function download(array $urls): ?string
    {
        return self::downloadBest($urls, 10000);
    }

    /**
     * Prefer the largest valid TrueType payload so subset CDNs lose to full fonts.
     *
     * @param  list<string>  $urls
     */
    public static function downloadBest(array $urls, int $minimum): ?string
    {
        $best = null;
        $bestSize = 0;

        foreach ($urls as $url) {
            $data = self::fetch($url);
            if (! is_string($data) || ! self::isTrueType($data) || strlen($data) < $minimum) {
                continue;
            }
            if (self::isVariableFont($data)) {
                continue;
            }
            if (strlen($data) > $bestSize) {
                $best = $data;
                $bestSize = strlen($data);
            }
        }

        return $best;
    }

    protected static function fetch(string $url): ?string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 90,
                'follow_location' => 1,
                'header' => "User-Agent: laravel-unicode-pdf\r\n",
            ],
            'https' => [
                'timeout' => 90,
                'follow_location' => 1,
                'header' => "User-Agent: laravel-unicode-pdf\r\n",
            ],
        ]);

        $data = @file_get_contents($url, false, $context);
        if (is_string($data) && $data !== '') {
            return $data;
        }

        if (! function_exists('curl_init')) {
            return null;
        }

        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_USERAGENT => 'laravel-unicode-pdf',
        ]);
        $data = curl_exec($handle);
        curl_close($handle);

        return is_string($data) && $data !== '' ? $data : null;
    }

    protected static function isVariableFont(string $binary): bool
    {
        $numTables = unpack('n', substr($binary, 4, 2))[1] ?? 0;
        $offset = 12;
        for ($i = 0; $i < $numTables; $i++) {
            if (substr($binary, $offset, 4) === 'fvar') {
                return true;
            }
            $offset += 16;
        }

        return false;
    }

    protected static function isTrueType(string $binary): bool
    {
        if (strlen($binary) < 10000) {
            return false;
        }

        $magic = substr($binary, 0, 4);

        return in_array($magic, ["\x00\x01\x00\x00", 'true', 'typ1'], true);
    }

    protected static function isTesting(): bool
    {
        return defined('PHPUNIT_COMPOSER_INSTALL');
    }
}
