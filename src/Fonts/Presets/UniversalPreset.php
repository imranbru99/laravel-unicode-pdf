<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Fonts\Presets;

class UniversalPreset implements PresetInterface
{
    public function getName(): string
    {
        return 'universal';
    }

    public function getDefaultFont(): string
    {
        return 'Noto Sans';
    }

    public function getFallbackFonts(): array
    {
        return [
            'Noto Sans',
            'AI-Borno',
            'Noto Sans Bengali',
            'Noto Sans Arabic',
            'Noto Sans Devanagari',
            'Noto Sans Thai',
            'Noto Sans Hebrew',
            'Noto Sans Tamil',
            'Noto Sans Telugu',
            'Noto Sans Malayalam',
            'Noto Sans Gujarati',
            'Noto Sans Gurmukhi',
            'Noto Sans Kannada',
            'Noto Sans Sinhala',
            'Noto Sans CJK SC',
            'Noto Sans CJK TC',
            'Noto Sans CJK JP',
            'Noto Sans CJK KR',
            'Noto Color Emoji',
        ];
    }

    public function getDirection(): string
    {
        return 'auto';
    }

    public function getOptions(): array
    {
        return [
            'bidi' => true,
            'complex_shaping' => true,
        ];
    }
}
