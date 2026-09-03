<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Engines;

use ImranDev\UnicodePdf\Exceptions\PdfGenerationException;
use ImranDev\UnicodePdf\Exceptions\UnsupportedEngineException;
use TCPDF;

class TcpdfEngine extends AbstractPdfEngine
{
    public function getName(): string
    {
        return 'tcpdf';
    }

    public function supports(string $capability): bool
    {
        return match (strtolower($capability)) {
            'unicode' => true,
            'rtl' => true,
            'font-shaping' => false,
            'svg' => true,
            'encryption' => true,
            'attachments' => true,
            'javascript' => false,
            default => false,
        };
    }

    public function output(): string
    {
        if (! class_exists(TCPDF::class)) {
            throw UnsupportedEngineException::missingDriver('tcpdf', 'tecnickcom/tcpdf');
        }

        try {
            $prepared = $this->getPreparedContent();

            $orientation = str_starts_with(strtolower($this->orientation), 'l') ? 'L' : 'P';
            $paperSize = is_array($this->paper) ? $this->paper : strtoupper((string) $this->paper);

            $pdf = new TCPDF($orientation, 'mm', $paperSize, true, 'UTF-8', false);

            if ($prepared['detected_direction'] === 'rtl') {
                $pdf->setRTL(true);
            }

            $pdf->SetMargins(
                $this->margins['left'],
                $this->margins['top'],
                $this->margins['right']
            );

            // Metadata
            if (! empty($this->metadata['title'])) {
                $pdf->SetTitle($this->metadata['title']);
            }
            if (! empty($this->metadata['author'])) {
                $pdf->SetAuthor($this->metadata['author']);
            }

            // Protection
            if (! empty($this->protectionOptions)) {
                $userPass = $this->protectionOptions['user_password'] ?? '';
                $ownerPass = $this->protectionOptions['owner_password'] ?? '';
                $permissions = $this->protectionOptions['permissions'] ?? ['print', 'copy'];
                $pdf->SetProtection($permissions, $userPass, $ownerPass);
            }

            $pdf->AddPage();
            $pdf->writeHTML($prepared['html'], true, false, true, false, '');

            return $pdf->Output('', 'S');
        } catch (\Throwable $e) {
            throw PdfGenerationException::renderingFailed('tcpdf', $e->getMessage(), $e);
        }
    }
}
