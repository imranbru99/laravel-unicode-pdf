<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Fonts;

class FontFallback
{
    /**
     * @param  array<string>  $configuredFallbacks
     */
    public function __construct(
        protected array $configuredFallbacks = []
    ) {}

    /**
     * Get fallback chain for a primary font.
     *
     * @param  array<string>  $extraFallbacks
     * @return array<string>
     */
    public function getChain(string $primaryFont, array $extraFallbacks = []): array
    {
        $chain = [$primaryFont];

        foreach (array_merge($this->configuredFallbacks, $extraFallbacks) as $font) {
            if (! in_array($font, $chain, true) && trim($font) !== '') {
                $chain[] = trim($font);
            }
        }

        return $chain;
    }

    /**
     * Build CSS font-family string.
     *
     * @param  array<string>  $extraFallbacks
     */
    public function toCss(string $primaryFont, array $extraFallbacks = [], string $generic = 'sans-serif'): string
    {
        $chain = $this->getChain($primaryFont, $extraFallbacks);
        $escaped = array_map(fn ($f) => "'".addslashes($f)."'", $chain);
        if ($generic !== '') {
            $escaped[] = $generic;
        }

        return implode(', ', $escaped);
    }
}
