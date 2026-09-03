<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Exceptions;

class PdfGenerationException extends UnicodePdfException
{
    public static function renderingFailed(string $engine, string $reason, ?\Throwable $previous = null): self
    {
        return new self(
            "PDF generation failed using engine \"{$engine}\": {$reason}",
            0,
            $previous
        );
    }
}
