<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Fonts\Presets;

class CjkPreset implements PresetInterface
{
    public function getName(): string
    {
        return 'cjk';
    }

    public function getDefaultFont(): string
    {
        return 'Noto Sans CJK SC';
    }

    public function getFallbackFonts(): array
    {
        return [
            'Noto Sans CJK SC',
            'Noto Sans CJK TC',
            'Noto Sans CJK JP',
            'Noto Sans CJK KR',
            'Noto Sans',
        ];
    }

    public function getDirection(): string
    {
        return 'ltr';
    }

    public function getOptions(): array
    {
        return [];
    }
}
