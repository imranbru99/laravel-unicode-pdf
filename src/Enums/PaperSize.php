<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Enums;

enum PaperSize: string
{
    case A3 = 'a3';
    case A4 = 'a4';
    case A5 = 'a5';
    case A6 = 'a6';
    case Letter = 'letter';
    case Legal = 'legal';
    case Tabloid = 'tabloid';
    case Executive = 'executive';
    case Folio = 'folio';
    case B4 = 'b4';
    case B5 = 'b5';

    /**
     * Width and height in points (1/72 inch).
     *
     * @return array{0: float, 1: float}
     */
    public function dimensions(): array
    {
        return match ($this) {
            self::A3 => [841.89, 1190.55],
            self::A4 => [595.28, 841.89],
            self::A5 => [419.53, 595.28],
            self::A6 => [297.64, 419.53],
            self::Letter => [612.00, 792.00],
            self::Legal => [612.00, 1008.00],
            self::Tabloid => [792.00, 1224.00],
            self::Executive => [522.00, 756.00],
            self::Folio => [612.00, 936.00],
            self::B4 => [708.66, 1000.63],
            self::B5 => [498.90, 708.66],
        };
    }
}
