<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Fonts\Presets;

class ScriptPreset implements PresetInterface
{
    /**
     * @param  array<int, string>  $fallbackFonts
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        protected string $name,
        protected string $defaultFont,
        protected array $fallbackFonts = [],
        protected string $direction = 'ltr',
        protected array $options = [],
    ) {
        if ($this->fallbackFonts === []) {
            $this->fallbackFonts = [$this->defaultFont, 'Noto Sans'];
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDefaultFont(): string
    {
        return $this->defaultFont;
    }

    public function getFallbackFonts(): array
    {
        return $this->fallbackFonts;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
