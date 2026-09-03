<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Fonts;

use ImranDev\UnicodePdf\Unicode\ScriptDetector;

class FontDetector
{
    /**
     * Map of scripts to standard recommended fonts.
     *
     * @var array<string, string>
     */
    protected static array $scriptFontMap = [
        'Bengali' => 'AI-Borno',
        'Arabic' => 'Noto Sans Arabic',
        'Devanagari' => 'Noto Sans Devanagari',
        'Tamil' => 'Noto Sans Tamil',
        'Telugu' => 'Noto Sans Telugu',
        'Malayalam' => 'Noto Sans Malayalam',
        'Gujarati' => 'Noto Sans Gujarati',
        'Gurmukhi' => 'Noto Sans Gurmukhi',
        'Kannada' => 'Noto Sans Kannada',
        'Sinhala' => 'Noto Sans Sinhala',
        'Thai' => 'Noto Sans Thai',
        'Hebrew' => 'Noto Sans Hebrew',
        'CJK' => 'Noto Sans CJK SC',
        'Japanese' => 'Noto Sans CJK JP',
        'Korean' => 'Noto Sans CJK KR',
        'Cyrillic' => 'Noto Sans',
        'Greek' => 'Noto Sans',
        'Ethiopic' => 'Noto Sans Ethiopic',
        'Armenian' => 'Noto Sans Armenian',
        'Georgian' => 'Noto Sans Georgian',
        'Latin' => 'Noto Sans',
    ];

    public function __construct(
        protected ScriptDetector $scriptDetector = new ScriptDetector
    ) {}

    /**
     * Diagnose text for scripts and missing glyph / font coverage.
     *
     * @param  array<string>  $registeredFonts
     * @return array{
     *     detected_scripts: array<string, int>,
     *     dominant_script: string,
     *     primary_font: string,
     *     missing_scripts: array<string>,
     *     suggested_fonts: array<string>
     * }
     */
    public function diagnose(string $text, string $primaryFont, array $registeredFonts = []): array
    {
        $detected = $this->scriptDetector->detect($text);
        $dominant = $this->scriptDetector->getDominantScript($text);

        $missingScripts = [];
        $suggestedFonts = [];

        foreach (array_keys($detected) as $script) {
            if ($script === 'Latin') {
                continue;
            }

            $recommendedFont = self::$scriptFontMap[$script] ?? null;
            if ($recommendedFont) {
                // If primary font does not match the recommended script font
                if (! str_contains(strtolower($primaryFont), strtolower($script))) {
                    $missingScripts[] = $script;
                    $suggestedFonts[] = $recommendedFont;
                }
            }
        }

        return [
            'detected_scripts' => $detected,
            'dominant_script' => $dominant,
            'primary_font' => $primaryFont,
            'missing_scripts' => array_unique($missingScripts),
            'suggested_fonts' => array_unique($suggestedFonts),
        ];
    }

    /**
     * Get recommended font for a script.
     */
    public static function getRecommendedFontForScript(string $script): ?string
    {
        return self::$scriptFontMap[$script] ?? null;
    }
}
