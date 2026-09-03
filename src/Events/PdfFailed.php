<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Events;

use ImranDev\UnicodePdf\UnicodePdfDocument;
use Throwable;

class PdfFailed
{
    public function __construct(
        public UnicodePdfDocument $document,
        public Throwable $exception
    ) {}
}
