<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Engines;

use Dompdf\Dompdf;
use Dompdf\Options;
use ImranDev\UnicodePdf\Exceptions\PdfGenerationException;
use ImranDev\UnicodePdf\Exceptions\UnsupportedEngineException;

class DompdfEngine extends AbstractPdfEngine
{
    public function getName(): string
    {
        return 'dompdf';
    }

    public function supports(string $capability): bool
    {
        return match (strtolower($capability)) {
            'unicode' => true,
            'svg' => true,
            'rtl' => false,
            'font-shaping' => false,
            'javascript' => false,
            'encryption' => true,
            default => false,
        };
    }

    public function output(): string
    {
        if (! class_exists(Dompdf::class)) {
            throw UnsupportedEngineException::missingDriver('dompdf', 'dompdf/dompdf');
        }

        try {
            $prepared = $this->getPreparedContent();

            $options = new Options;
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', $this->securityManager ? false : false);
            $options->set('defaultFont', $this->primaryFont);
            $options->set('chroot', base_path());

            if ($this->fontManager) {
                $fontCache = config('unicode-pdf.font_cache', storage_path('app/unicode-pdf/cache/fonts'));
                if (! is_dir($fontCache)) {
                    @mkdir($fontCache, 0755, true);
                }
                $options->set('fontCache', $fontCache);
            }

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($prepared['html'], 'UTF-8');

            $paperSize = is_array($this->paper) ? $this->paper : strtolower((string) $this->paper);
            $dompdf->setPaper($paperSize, $this->orientation);

            $dompdf->render();

            // Watermark & Page numbers via canvas if requested
            if ($this->watermarkText) {
                $canvas = $dompdf->getCanvas();
                $canvas->page_text(
                    $canvas->get_width() / 2 - 50,
                    $canvas->get_height() / 2,
                    $this->watermarkText,
                    null,
                    40,
                    [0.8, 0.8, 0.8],
                    0,
                    0,
                    -45
                );
            }

            if ($this->pageNumberFormat) {
                $canvas = $dompdf->getCanvas();
                $canvas->page_text(
                    $canvas->get_width() / 2,
                    $canvas->get_height() - 25,
                    $this->pageNumberFormat,
                    null,
                    9,
                    [0.3, 0.3, 0.3]
                );
            }

            // Protection/Encryption
            if (! empty($this->protectionOptions)) {
                $userPass = $this->protectionOptions['user_password'] ?? '';
                $ownerPass = $this->protectionOptions['owner_password'] ?? null;
                $permissions = $this->protectionOptions['permissions'] ?? ['print'];
                $dompdf->getCanvas()->get_cpdf()->setEncryption($userPass, $ownerPass, $permissions);
            }

            $output = $dompdf->output();
            if ($output === null) {
                throw new PdfGenerationException('Dompdf failed to produce PDF output string.');
            }

            return $output;
        } catch (\Throwable $e) {
            throw PdfGenerationException::renderingFailed('dompdf', $e->getMessage(), $e);
        }
    }
}
