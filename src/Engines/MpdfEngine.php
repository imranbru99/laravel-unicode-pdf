<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Engines;

use ImranDev\UnicodePdf\Exceptions\PdfGenerationException;
use ImranDev\UnicodePdf\Exceptions\UnsupportedEngineException;
use Mpdf\Mpdf;

class MpdfEngine extends AbstractPdfEngine
{
    public function getName(): string
    {
        return 'mpdf';
    }

    public function supports(string $capability): bool
    {
        return match (strtolower($capability)) {
            'unicode' => true,
            'rtl' => true,
            'font-shaping' => true,
            'svg' => true,
            'encryption' => true,
            'attachments' => true,
            'javascript' => false,
            default => false,
        };
    }

    public function output(): string
    {
        if (! class_exists(Mpdf::class)) {
            throw UnsupportedEngineException::missingDriver('mpdf', 'mpdf/mpdf');
        }

        try {
            $prepared = $this->getPreparedContent();

            $paperSize = is_array($this->paper) ? $this->paper : strtoupper((string) $this->paper);
            $orientation = str_starts_with(strtolower($this->orientation), 'l') ? 'L' : 'P';
            $format = is_string($paperSize) ? "{$paperSize}-{$orientation}" : $paperSize;

            $config = [
                'mode' => 'utf-8',
                'format' => $format,
                'margin_left' => $this->margins['left'],
                'margin_right' => $this->margins['right'],
                'margin_top' => $this->margins['top'],
                'margin_bottom' => $this->margins['bottom'],
                'autoScriptToLang' => true,
                'autoLangToFont' => true,
                'default_font' => $this->primaryFont,
                'tempDir' => config('unicode-pdf.font_cache', storage_path('app/unicode-pdf/cache')),
            ];

            $mpdf = new Mpdf($config);

            // RTL Direction
            if ($prepared['detected_direction'] === 'rtl') {
                $mpdf->SetDirectionality('rtl');
            }

            // Watermark
            if ($this->watermarkText) {
                $mpdf->SetWatermarkText($this->watermarkText, $this->watermarkOpacity);
                $mpdf->showWatermarkText = true;
            }

            // Headers & Footers
            if ($this->headerHtml) {
                $mpdf->SetHTMLHeader($this->headerHtml);
            }

            if ($this->footerHtml) {
                $mpdf->SetHTMLFooter($this->footerHtml);
            } elseif ($this->pageNumberFormat) {
                $mpdf->setFooter('{PAGENO} / {nbpg}');
            }

            // Metadata
            if (! empty($this->metadata['title'])) {
                $mpdf->SetTitle($this->metadata['title']);
            }
            if (! empty($this->metadata['author'])) {
                $mpdf->SetAuthor($this->metadata['author']);
            }
            if (! empty($this->metadata['subject'])) {
                $mpdf->SetSubject($this->metadata['subject']);
            }
            if (! empty($this->metadata['keywords'])) {
                $mpdf->SetKeywords($this->metadata['keywords']);
            }

            // Protection
            if (! empty($this->protectionOptions)) {
                $userPass = $this->protectionOptions['user_password'] ?? '';
                $ownerPass = $this->protectionOptions['owner_password'] ?? '';
                $permissions = $this->protectionOptions['permissions'] ?? ['print', 'copy'];
                $mpdf->SetProtection($permissions, $userPass, $ownerPass);
            }

            $mpdf->WriteHTML($prepared['html']);

            return $mpdf->Output('', 'S');
        } catch (\Throwable $e) {
            throw PdfGenerationException::renderingFailed('mpdf', $e->getMessage(), $e);
        }
    }
}
