<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Fonts;

use ImranDev\UnicodePdf\Contracts\FontResolverInterface;
use ImranDev\UnicodePdf\Unicode\ScriptDetector;

class FontResolver implements FontResolverInterface
{
    public function __construct(
        protected ScriptDetector $scriptDetector = new ScriptDetector
    ) {}

    /**
     * Resolve fonts based on scripts present in the text.
     *
     * @return array<string>
     */
    public function resolve(string $text, ?string $defaultFont = 'Noto Sans'): array
    {
        $defaultFont = $defaultFont ?: 'Noto Sans';
        $detectedScripts = $this->scriptDetector->detect($text);

        $resolved = [$defaultFont];

        foreach (array_keys($detectedScripts) as $script) {
            $font = $this->resolveForScript($script);
            if ($font && ! in_array($font, $resolved, true)) {
                $resolved[] = $font;
            }
        }

        return $resolved;
    }

    /**
     * Resolve font family specifically for a script name.
     */
    public function resolveForScript(string $script): ?string
    {
        return FontDetector::getRecommendedFontForScript($script);
    }

    /**
     * Generate CSS font-family string combining primary and fallback fonts.
     *
     * @param  array<string>  $fonts
     */
    public function buildFontFamilyStack(array $fonts, string $genericFallback = 'sans-serif'): string
    {
        $quoted = array_map(function ($font) {
            $trimmed = trim($font, "\"' ");

            return "\"{$trimmed}\"";
        }, $fonts);

        if (! empty($genericFallback)) {
            $quoted[] = $genericFallback;
        }

        return implode(', ', array_unique($quoted));
    }
}
