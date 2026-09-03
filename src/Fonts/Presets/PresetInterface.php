<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Fonts\Presets;

interface PresetInterface
{
    /**
     * Get preset name.
     */
    public function getName(): string;

    /**
     * Get default font for this preset.
     */
    public function getDefaultFont(): string;

    /**
     * Get fallback fonts for this preset.
     *
     * @return array<string>
     */
    public function getFallbackFonts(): array;

    /**
     * Get text direction for this preset ('ltr' | 'rtl' | 'auto').
     */
    public function getDirection(): string;

    /**
     * Get custom configuration settings for this preset.
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array;
}
