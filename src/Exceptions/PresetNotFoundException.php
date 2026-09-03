<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Exceptions;

class PresetNotFoundException extends UnicodePdfException
{
    public static function preset(string $name): self
    {
        return new self("Typography preset \"{$name}\" is not registered.");
    }

    public static function profile(string $name): self
    {
        return new self("PDF profile \"{$name}\" is not defined in config('unicode-pdf.profiles').");
    }
}
