<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Events;

use ImranDev\UnicodePdf\UnicodePdfDocument;

class PdfGenerating
{
    public function __construct(
        public UnicodePdfDocument $document
    ) {}
}
