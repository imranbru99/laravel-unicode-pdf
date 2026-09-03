<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Concerns;

use ImranDev\UnicodePdf\UnicodePdfDocument;

interface Pdfable
{
    /**
     * Build a configured Unicode PDF document for this object.
     */
    public function toPdf(): UnicodePdfDocument;
}
