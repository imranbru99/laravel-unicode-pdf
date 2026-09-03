<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Enums;

enum Engine: string
{
    case Native = 'native';
    case Dompdf = 'dompdf';
    case Mpdf = 'mpdf';
    case Tcpdf = 'tcpdf';
    case Browsershot = 'browsershot';
    case Null = 'null';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Native => 'Native (built-in)',
            self::Dompdf => 'Dompdf',
            self::Mpdf => 'mPDF',
            self::Tcpdf => 'TCPDF',
            self::Browsershot => 'Browsershot (Chromium)',
            self::Null => 'Null (testing)',
        };
    }

    public function composerPackage(): ?string
    {
        return match ($this) {
            self::Native => null,
            self::Dompdf => 'dompdf/dompdf',
            self::Mpdf => 'mpdf/mpdf',
            self::Tcpdf => 'tecnickcom/tcpdf',
            self::Browsershot => 'spatie/browsershot',
            self::Null => null,
        };
    }
}
