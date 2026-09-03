<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Fonts\Presets;

class IndianPreset implements PresetInterface
{
    public function getName(): string
    {
        return 'indian';
    }

    public function getDefaultFont(): string
    {
        return 'Noto Sans Devanagari';
    }

    public function getFallbackFonts(): array
    {
        return [
            'Noto Sans Devanagari',
            'Noto Sans Bengali',
            'Noto Sans Tamil',
            'Noto Sans Telugu',
            'Noto Sans Malayalam',
            'Noto Sans Gujarati',
            'Noto Sans Gurmukhi',
            'Noto Sans Kannada',
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
        ];
    }
}
