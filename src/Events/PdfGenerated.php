<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Events;

use ImranDev\UnicodePdf\UnicodePdfDocument;

class PdfGenerated
{
    public function __construct(
        public UnicodePdfDocument $document,
        public string $binary,
        public float $durationMs = 0.0
    ) {}

    public function size(): int
    {
        return strlen($this->binary);
    }
}
