<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Enums;

enum Preset: string
{
    case Bengali = 'bengali';
    case Arabic = 'arabic';
    case Indian = 'indian';
    case Cjk = 'cjk';
    case Universal = 'universal';
    case Thai = 'thai';
    case Hebrew = 'hebrew';
    case Persian = 'persian';
    case Urdu = 'urdu';
    case Tamil = 'tamil';
    case Korean = 'korean';
    case Japanese = 'japanese';
    case Vietnamese = 'vietnamese';
    case Greek = 'greek';
    case Cyrillic = 'cyrillic';
    case Ethiopic = 'ethiopic';
    case Khmer = 'khmer';
    case Myanmar = 'myanmar';
    case Sinhala = 'sinhala';
    case Latin = 'latin';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
