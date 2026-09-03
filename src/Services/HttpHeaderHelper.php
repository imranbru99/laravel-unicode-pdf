<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Services;

use Illuminate\Http\Response;

class HttpHeaderHelper
{
    /**
     * Sanitize filename to prevent HTTP header injection.
     */
    public static function sanitizeFilename(string $filename): string
    {
        // Remove line breaks, carriage returns, null bytes
        $clean = str_replace(["\r", "\n", "\0"], '', $filename);
        $clean = trim($clean);

        if ($clean === '' || $clean === '.pdf') {
            return 'document.pdf';
        }

        if (! str_ends_with(strtolower($clean), '.pdf')) {
            $clean .= '.pdf';
        }

        return $clean;
    }

    /**
     * Create Content-Disposition header value with UTF-8 encoding support (RFC 6266 / RFC 5987).
     */
    public static function makeContentDisposition(string $filename, string $disposition = 'attachment'): string
    {
        $sanitized = self::sanitizeFilename($filename);

        // ASCII fallback name
        $asciiFallback = preg_replace('/[^\x20-\x7E]/', '_', $sanitized);
        $asciiFallback = str_replace('"', '', (string) $asciiFallback);
        if ($asciiFallback === '' || $asciiFallback === '.pdf') {
            $asciiFallback = 'document.pdf';
        }

        $encodedUtf8 = rawurlencode($sanitized);

        return "{$disposition}; filename=\"{$asciiFallback}\"; filename*=UTF-8''{$encodedUtf8}";
    }

    /**
     * Create download response.
     */
    public static function makeDownloadResponse(string $pdfContent, string $filename = 'document.pdf'): Response
    {
        $disposition = self::makeContentDisposition($filename, 'attachment');

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition,
            'Content-Length' => (string) strlen($pdfContent),
            'Cache-Control' => 'private, no-transform, no-store, must-revalidate',
        ]);
    }

    /**
     * Create streamed response for browser preview.
     */
    public static function makeStreamResponse(string $pdfContent, string $filename = 'document.pdf'): Response
    {
        $disposition = self::makeContentDisposition($filename, 'inline');

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition,
            'Content-Length' => (string) strlen($pdfContent),
            'Cache-Control' => 'private, no-transform, no-store, must-revalidate',
        ]);
    }
}
