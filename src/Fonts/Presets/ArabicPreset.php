<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Fonts\Presets;

class ArabicPreset implements PresetInterface
{
    public function getName(): string
    {
        return 'arabic';
    }

    public function getDefaultFont(): string
    {
        return 'Noto Sans Arabic';
    }

    public function getFallbackFonts(): array
    {
        return [
            'Noto Sans Arabic',
            'Amiri',
            'Scheherazade New',
            'Noto Sans',
        ];
    }

    public function getDirection(): string
    {
        return 'rtl';
    }

    public function getOptions(): array
    {
        return [
            'bidi' => true,
            'rtl' => true,
            'script' => 'Arabic',
        ];
    }
}
