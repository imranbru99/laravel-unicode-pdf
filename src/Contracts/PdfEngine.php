<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Contracts;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

interface PdfEngine
{
    /**
     * Load raw HTML content.
     */
    public function loadHtml(string $html): static;

    /**
     * Load a Blade view template with data.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $mergeData
     */
    public function loadView(string $view, array $data = [], array $mergeData = []): static;

    /**
     * Set paper size and orientation.
     *
     * @param  string|array<int|float>  $paper
     */
    public function setPaper(string|array $paper, string $orientation = 'portrait'): static;

    /**
     * Set document orientation ('portrait' | 'landscape').
     */
    public function setOrientation(string $orientation): static;

    /**
     * Set page margins.
     */
    public function setMargins(
        int|float $top = 10,
        int|float $right = 10,
        int|float $bottom = 10,
        int|float $left = 10,
        string $unit = 'mm'
    ): static;

    /**
     * Set primary font family.
     */
    public function setFont(string $font): static;

    /**
     * Set fallback fonts stack.
     *
     * @param  array<string>  $fonts
     */
    public function setFallbackFonts(array $fonts): static;

    /**
     * Set document text direction ('auto' | 'ltr' | 'rtl').
     */
    public function setDirection(string $direction): static;

    /**
     * Enable or configure bi-directional text processing.
     */
    public function setBidi(bool $enabled = true): static;

    /**
     * Set document metadata.
     *
     * @param  array<string, string>  $metadata
     */
    public function setMetadata(array $metadata): static;

    /**
     * Add a watermark text.
     */
    public function setWatermark(string $text, float $opacity = 0.2): static;

    /**
     * Protect PDF with user/owner password and permissions.
     *
     * @param  array<string, mixed>  $options
     */
    public function protect(array $options): static;

    /**
     * Configure header template or HTML.
     *
     * @param  array<string, mixed>  $data
     */
    public function setHeader(string $html, array $data = []): static;

    /**
     * Configure footer template or HTML.
     *
     * @param  array<string, mixed>  $data
     */
    public function setFooter(string $html, array $data = []): static;

    /**
     * Configure page numbering format.
     */
    public function setPageNumbers(string $format = '{PAGE_NUM} / {PAGE_COUNT}'): static;

    /**
     * Generate and return raw binary PDF output.
     */
    public function output(): string;

    /**
     * Save generated PDF to local filesystem.
     */
    public function save(string $path): bool;

    /**
     * Save generated PDF to a Laravel storage disk.
     */
    public function store(string $path, ?string $disk = null): bool;

    /**
     * Return a StreamedResponse to stream PDF in browser.
     */
    public function stream(string $filename = 'document.pdf'): StreamedResponse|Response;

    /**
     * Return a Response to force download PDF.
     */
    public function download(string $filename = 'document.pdf'): Response;

    /**
     * Check if this engine natively supports a given capability.
     * Capabilities: 'rtl', 'font-shaping', 'svg', 'javascript', 'encryption', 'attachments', 'font-subsetting'
     */
    public function supports(string $capability): bool;

    /**
     * Return the unique identifier name for this engine.
     */
    public function getName(): string;
}
