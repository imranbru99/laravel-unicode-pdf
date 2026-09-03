<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Fonts\Presets;

class BengaliPreset implements PresetInterface
{
    public function getName(): string
    {
        return 'bengali';
    }

    public function getDefaultFont(): string
    {
        return 'AI-Borno';
    }

    public function getFallbackFonts(): array
    {
        return [
            'AI-Borno',
            'Noto Sans Bengali',
            'SolaimanLipi',
            'Kalpurush',
            'Noto Sans',
        ];
    }

    public function getDirection(): string
    {
        return 'ltr';
    }

    public function getOptions(): array
    {
        return [
            'complex_shaping' => true,
            'script' => 'Bengali',
        ];
    }
}
