<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Enums;

enum Orientation: string
{
    case Portrait = 'portrait';
    case Landscape = 'landscape';

    public function short(): string
    {
        return $this === self::Landscape ? 'L' : 'P';
    }
}
