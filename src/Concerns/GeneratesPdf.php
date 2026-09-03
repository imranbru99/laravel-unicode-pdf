<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Concerns;

use ImranDev\UnicodePdf\Facades\UnicodePdf;
use ImranDev\UnicodePdf\UnicodePdfDocument;

trait GeneratesPdf
{
    /**
     * Build a PDF document from this object's view and data.
     */
    public function toPdf(): UnicodePdfDocument
    {
        $document = UnicodePdf::loadView($this->pdfView(), $this->pdfData());

        if (method_exists($this, 'pdfPreset') && is_string($preset = $this->pdfPreset())) {
            $document->preset($preset);
        }

        if (method_exists($this, 'pdfFilename') && is_string($filename = $this->pdfFilename())) {
            $document->name($filename);
        }

        return $document;
    }

    /**
     * Blade view used to render this object's PDF.
     */
    abstract protected function pdfView(): string;

    /**
     * Data passed to the PDF view.
     *
     * @return array<string, mixed>
     */
    protected function pdfData(): array
    {
        return ['model' => $this];
    }
}
