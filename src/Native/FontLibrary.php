<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Native;

use ImranDev\UnicodePdf\Fonts\FontManager;

class FontLibrary
{
    /**
     * @var array<string, array<string, string>>
     */
    protected array $paths = [];

    /**
     * @param  list<string>  $searchDirectories
     */
    public function __construct(
        protected ?FontManager $fontManager = null,
        protected array $searchDirectories = []
    ) {
        $this->discover();
    }

    public function discover(): void
    {
        if ($this->fontManager) {
            foreach ($this->fontManager->all() as $family => $definition) {
                foreach (['regular', 'bold', 'italic', 'bold_italic'] as $style) {
                    $path = $definition[$style] ?? null;
                    if (is_string($path) && is_readable($path)) {
                        $this->paths[$this->normalize($family)][$style] = $path;
                    }
                }
            }

            foreach ($this->searchDirectories as $directory) {
                if (is_dir($directory)) {
                    $this->fontManager->discoverDirectory($directory);
                }
            }

            foreach ($this->fontManager->all() as $family => $definition) {
                foreach (['regular', 'bold', 'italic', 'bold_italic'] as $style) {
                    $path = $definition[$style] ?? null;
                    if (is_string($path) && is_readable($path)) {
                        $this->paths[$this->normalize($family)][$style] = $path;
                    }
                }
            }
        }

        foreach ($this->searchDirectories as $directory) {
            $this->scanDirectory($directory);
        }

        $this->registerKnownSystemFonts();
    }

    public function pathFor(string $family, string $style = 'regular'): ?string
    {
        $key = $this->normalize($family);
        $style = $this->normalizeStyle($style);

        if (isset($this->paths[$key][$style])) {
            return $this->paths[$key][$style];
        }

        if (isset($this->paths[$key]['regular'])) {
            return $this->paths[$key]['regular'];
        }

        foreach ($this->paths as $registered => $styles) {
            if (str_contains($registered, $key) || str_contains($key, $registered)) {
                return $styles[$style] ?? $styles['regular'] ?? null;
            }
        }

        return null;
    }

    /**
     * First TTF that contains the codepoint, preferring the requested families.
     *
     * @param  list<string>  $families
     */
    public function fontForCodepoint(int $codepoint, array $families = [], string $style = 'regular'): ?TtfFont
    {
        $candidates = [];
        foreach ($families as $family) {
            $path = $this->pathFor($family, $style);
            if ($path) {
                $candidates[] = $path;
            }
        }

        foreach ($this->paths as $styles) {
            $path = $styles[$style] ?? $styles['regular'] ?? null;
            if ($path && ! in_array($path, $candidates, true)) {
                $candidates[] = $path;
            }
        }

        foreach ($candidates as $path) {
            try {
                $font = TtfFont::load($path);
                if ($font->hasGlyph($codepoint)) {
                    return $font;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function families(): array
    {
        return array_keys($this->paths);
    }

    protected function scanDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $files = glob(rtrim($directory, '/\\').DIRECTORY_SEPARATOR.'*.{ttf,TTF,ttc,TTC}', GLOB_BRACE) ?: [];
        foreach ($files as $file) {
            try {
                $font = TtfFont::load($file);
                $key = $this->normalize($font->family);
                $style = str_contains(strtolower(basename($file)), 'bold') ? 'bold' : 'regular';
                $this->paths[$key][$style] ??= $file;
                $this->fontManager?->register([
                    'family' => $font->family,
                    'regular' => $this->paths[$key]['regular'] ?? $file,
                    'bold' => $this->paths[$key]['bold'] ?? null,
                ]);
            } catch (\Throwable) {
                continue;
            }
        }
    }

    protected function normalize(string $family): string
    {
        return strtolower(trim($family));
    }

    protected function normalizeStyle(string $style): string
    {
        return match (strtolower($style)) {
            'bold', 'b', '700' => 'bold',
            'italic', 'oblique', 'i' => 'italic',
            'bolditalic', 'bold_italic', 'bold italic' => 'bold_italic',
            default => 'regular',
        };
    }

    /**
     * Package fonts directory shipped with laravel-unicode-pdf.
     */
    public static function packageFontPath(): string
    {
        return dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'fonts';
    }

    /**
     * Developer custom-font tree (AI-Borno dist) living next to the package.
     */
    public static function customFontPath(): string
    {
        if (function_exists('app')) {
            try {
                $app = app();
                if ($app->bound('config')) {
                    $configured = (string) config('unicode-pdf.custom_font_path', '');
                    if ($configured !== '' && is_dir($configured)) {
                        return $configured;
                    }
                }
            } catch (\Throwable) {
            }
        }

        $sibling = dirname(__DIR__, 3).DIRECTORY_SEPARATOR.'font'.DIRECTORY_SEPARATOR.'dist';
        if (is_dir($sibling)) {
            return $sibling;
        }

        return 'C:\\Users\\imran\\Documents\\server\\font\\dist';
    }

    protected function registerKnownSystemFonts(): void
    {
        $windows = (getenv('WINDIR') ?: 'C:\\Windows').DIRECTORY_SEPARATOR.'Fonts'.DIRECTORY_SEPARATOR;
        $known = [
            'AI-Borno' => [
                self::customFontPath().DIRECTORY_SEPARATOR.'AI-Borno-Regular.ttf',
                self::packageFontPath().DIRECTORY_SEPARATOR.'AI-Borno-Regular.ttf',
            ],
            'Noto Sans' => [$windows.'arial.ttf', $windows.'segoeui.ttf', $windows.'calibri.ttf'],
            'Noto Sans Bengali' => [$windows.'Nirmala.ttc', $windows.'Nirmala.ttf', $windows.'nirmala.ttf', $windows.'vrinda.ttf'],
            'Noto Sans Devanagari' => [$windows.'Nirmala.ttc', $windows.'Nirmala.ttf', $windows.'nirmala.ttf', $windows.'mangal.ttf'],
            'Noto Sans Tamil' => [$windows.'Nirmala.ttc', $windows.'Nirmala.ttf'],
            'Noto Sans Telugu' => [$windows.'Nirmala.ttc', $windows.'Nirmala.ttf'],
            'Noto Sans Malayalam' => [$windows.'Nirmala.ttc', $windows.'Nirmala.ttf'],
            'Noto Sans Gujarati' => [$windows.'Nirmala.ttc', $windows.'Nirmala.ttf'],
            'Noto Sans Gurmukhi' => [$windows.'Nirmala.ttc', $windows.'Nirmala.ttf'],
            'Noto Sans Kannada' => [$windows.'Nirmala.ttc', $windows.'Nirmala.ttf'],
            'Noto Sans Sinhala' => [$windows.'Nirmala.ttc', $windows.'Nirmala.ttf'],
            'Noto Sans Oriya' => [$windows.'Nirmala.ttc', $windows.'Nirmala.ttf'],
            'Noto Sans Arabic' => [$windows.'tahoma.ttf', $windows.'arial.ttf'],
            'Noto Sans Hebrew' => [$windows.'arial.ttf', $windows.'tahoma.ttf'],
            'Noto Sans Thai' => [$windows.'LeelawUI.ttf', $windows.'leelawui.ttf'],
            'Noto Sans CJK SC' => [$windows.'msyh.ttc', $windows.'simsun.ttc', $windows.'simsunb.ttf'],
            'Noto Sans CJK JP' => [$windows.'YuGothR.ttc', $windows.'msgothic.ttc'],
            'Noto Sans CJK KR' => [$windows.'malgun.ttf'],
        ];

        foreach ($known as $family => $paths) {
            if (isset($this->paths[$this->normalize($family)]['regular'])) {
                continue;
            }
            foreach ($paths as $path) {
                if (is_readable($path)) {
                    $this->paths[$this->normalize($family)]['regular'] = $path;
                    $this->fontManager?->register(['family' => $family, 'regular' => $path]);
                    break;
                }
            }
        }

        foreach ([
            self::customFontPath().DIRECTORY_SEPARATOR.'AI-Borno-Bold.ttf',
            self::packageFontPath().DIRECTORY_SEPARATOR.'AI-Borno-Bold.ttf',
        ] as $boldPath) {
            if (is_readable($boldPath)) {
                $this->paths['ai-borno']['bold'] = $boldPath;
                $this->fontManager?->register([
                    'family' => 'AI-Borno',
                    'regular' => $this->paths['ai-borno']['regular'] ?? $boldPath,
                    'bold' => $boldPath,
                ]);
                break;
            }
        }
    }
}
