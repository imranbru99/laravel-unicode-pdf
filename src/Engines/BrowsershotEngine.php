<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Engines;

use ImranDev\UnicodePdf\Exceptions\PdfGenerationException;
use ImranDev\UnicodePdf\Exceptions\UnsupportedEngineException;
use Spatie\Browsershot\Browsershot;

class BrowsershotEngine extends AbstractPdfEngine
{
    public function getName(): string
    {
        return 'browsershot';
    }

    public function supports(string $capability): bool
    {
        return match (strtolower($capability)) {
            'unicode' => true,
            'rtl' => true,
            'font-shaping' => true,
            'svg' => true,
            'javascript' => true,
            'encryption' => false,
            'attachments' => false,
            default => true,
        };
    }

    public function output(): string
    {
        if (! class_exists(Browsershot::class)) {
            throw UnsupportedEngineException::missingDriver('browsershot', 'spatie/browsershot');
        }

        try {
            $prepared = $this->getPreparedContent();

            $paperSize = is_array($this->paper) ? 'A4' : strtoupper((string) $this->paper);
            $landscape = str_starts_with(strtolower($this->orientation), 'l');

            $browsershot = Browsershot::html($prepared['html'])
                ->format($paperSize)
                ->margins(
                    $this->margins['top'],
                    $this->margins['right'],
                    $this->margins['bottom'],
                    $this->margins['left'],
                    $this->margins['unit']
                );

            if ($landscape) {
                $browsershot->landscape();
            }

            if ($this->headerHtml || $this->footerHtml || $this->pageNumberFormat) {
                $browsershot->showBrowserHeaderAndFooter();
                if ($this->headerHtml) {
                    $browsershot->headerHtml($this->headerHtml);
                }
                if ($this->footerHtml) {
                    $browsershot->footerHtml($this->footerHtml);
                }
            }

            return $browsershot->pdf();
        } catch (\Throwable $e) {
            throw PdfGenerationException::renderingFailed('browsershot', $e->getMessage(), $e);
        }
    }
}
