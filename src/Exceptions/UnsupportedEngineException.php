<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Exceptions;

class UnsupportedEngineException extends UnicodePdfException
{
    public static function notFound(string $engine): self
    {
        return new self(
            "PDF engine \"{$engine}\" is not registered. Available engines: [native, dompdf, mpdf, tcpdf, browsershot, null]."
        );
    }

    public static function missingDriver(string $engine, string $package): self
    {
        return new self(
            "PDF engine \"{$engine}\" requires package \"{$package}\". Install it using `composer require {$package}`."
        );
    }
}
