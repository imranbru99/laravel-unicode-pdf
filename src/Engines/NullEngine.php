<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Engines;

class NullEngine extends AbstractPdfEngine
{
    public function getName(): string
    {
        return 'null';
    }

    public function supports(string $capability): bool
    {
        return match ($capability) {
            'unicode' => true,
            'rtl' => true,
            'font-shaping' => true,
            'svg' => true,
            'encryption' => true,
            'attachments' => true,
            default => true,
        };
    }

    /**
     * Generate a structurally valid minimal PDF 1.4 binary string embedding the UTF-8 text and metadata.
     */
    public function output(): string
    {
        $prepared = $this->getPreparedContent();
        $rawText = strip_tags($prepared['html']);
        $rawText = preg_replace('/\s+/', ' ', trim($rawText));

        $title = $this->metadata['title'] ?? 'Unicode Document';
        $author = $this->metadata['author'] ?? 'laravel-unicode-pdf';
        $creationDate = 'D:'.date('YmdHis');

        $textStream = 'BT /F1 12 Tf 50 750 Td ('.addcslashes($rawText, "()\r\n\t").') Tj ET';
        $streamLen = strlen($textStream);

        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[2] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
        $objects[3] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595.28 841.89] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>';
        $objects[4] = "<< /Length {$streamLen} >>\nstream\n{$textStream}\nendstream";
        $objects[5] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[6] = '<< /Title ('.addcslashes($title, "()\r\n\t").') /Author ('.addcslashes($author, "()\r\n\t").") /CreationDate ({$creationDate}) >>";

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $xref = [0 => 0];

        foreach ($objects as $num => $body) {
            $xref[$num] = strlen($pdf);
            $pdf .= "{$num} 0 obj\n{$body}\nendobj\n";
        }

        $xrefStart = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $xref[$i]);
        }

        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R /Info 6 0 R >>\n";
        $pdf .= "startxref\n{$xrefStart}\n%%EOF";

        return $pdf;
    }
}
