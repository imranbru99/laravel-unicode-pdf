<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Engines;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use ImranDev\UnicodePdf\Contracts\PdfEngine;
use ImranDev\UnicodePdf\Exceptions\PdfGenerationException;
use ImranDev\UnicodePdf\Fonts\FontManager;
use ImranDev\UnicodePdf\Services\HttpHeaderHelper;
use ImranDev\UnicodePdf\Services\PdfRenderer;
use ImranDev\UnicodePdf\Services\SecurityManager;

abstract class AbstractPdfEngine implements PdfEngine
{
    protected string $html = '';

    protected string|array $paper = 'a4';

    protected string $orientation = 'portrait';

    /**
     * @var array{top: int|float, right: int|float, bottom: int|float, left: int|float, unit: string}
     */
    protected array $margins = [
        'top' => 10,
        'right' => 10,
        'bottom' => 10,
        'left' => 10,
        'unit' => 'mm',
    ];

    protected string $primaryFont = 'Noto Sans';

    /**
     * @var array<string>
     */
    protected array $fallbackFonts = [];

    protected string $direction = 'auto';

    protected bool $bidi = true;

    /**
     * @var array<string, string>
     */
    protected array $metadata = [];

    protected ?string $watermarkText = null;

    protected float $watermarkOpacity = 0.2;

    /**
     * @var array<string, mixed>
     */
    protected array $protectionOptions = [];

    protected ?string $headerHtml = null;

    protected ?string $footerHtml = null;

    protected ?string $pageNumberFormat = null;

    /**
     * @var array<string, mixed>
     */
    protected array $engineOptions = [];

    protected string $extraCss = '';

    protected string $htmlLang = 'en';

    public function __construct(
        protected ?ViewFactory $viewFactory = null,
        protected ?FontManager $fontManager = null,
        protected ?SecurityManager $securityManager = null,
        protected ?PdfRenderer $pdfRenderer = null
    ) {
        $this->securityManager = $securityManager ?? new SecurityManager;
        $this->pdfRenderer = $pdfRenderer ?? new PdfRenderer(fontManager: $this->fontManager);
    }

    public function loadHtml(string $html): static
    {
        $this->html = $html;

        return $this;
    }

    public function loadView(string $view, array $data = [], array $mergeData = []): static
    {
        if ($this->viewFactory) {
            $this->html = $this->viewFactory->make($view, $data, $mergeData)->render();
        } elseif (function_exists('view')) {
            $this->html = view($view, $data, $mergeData)->render();
        } else {
            throw new PdfGenerationException("Cannot render Blade view '{$view}': View factory not bound.");
        }

        return $this;
    }

    public function setPaper(string|array $paper, string $orientation = 'portrait'): static
    {
        $this->paper = $paper;
        $this->orientation = $orientation;

        return $this;
    }

    public function setOrientation(string $orientation): static
    {
        $this->orientation = $orientation;

        return $this;
    }

    public function setMargins(
        int|float $top = 10,
        int|float $right = 10,
        int|float $bottom = 10,
        int|float $left = 10,
        string $unit = 'mm'
    ): static {
        $this->margins = [
            'top' => $top,
            'right' => $right,
            'bottom' => $bottom,
            'left' => $left,
            'unit' => $unit,
        ];

        return $this;
    }

    public function setFont(string $font): static
    {
        $this->primaryFont = $font;

        return $this;
    }

    public function setFallbackFonts(array $fonts): static
    {
        $this->fallbackFonts = $fonts;

        return $this;
    }

    public function setDirection(string $direction): static
    {
        $this->direction = $direction;

        return $this;
    }

    public function setBidi(bool $enabled = true): static
    {
        $this->bidi = $enabled;

        return $this;
    }

    public function setMetadata(array $metadata): static
    {
        $this->metadata = array_merge($this->metadata, $metadata);

        return $this;
    }

    public function setWatermark(string $text, float $opacity = 0.2): static
    {
        $this->watermarkText = $text;
        $this->watermarkOpacity = $opacity;

        return $this;
    }

    public function protect(array $options): static
    {
        $this->protectionOptions = $options;

        return $this;
    }

    public function setHeader(string $html, array $data = []): static
    {
        if ($this->viewFactory && view()->exists($html)) {
            $this->headerHtml = $this->viewFactory->make($html, $data)->render();
        } else {
            $this->headerHtml = $html;
        }

        return $this;
    }

    public function setFooter(string $html, array $data = []): static
    {
        if ($this->viewFactory && view()->exists($html)) {
            $this->footerHtml = $this->viewFactory->make($html, $data)->render();
        } else {
            $this->footerHtml = $html;
        }

        return $this;
    }

    public function setPageNumbers(string $format = '{PAGE_NUM} / {PAGE_COUNT}'): static
    {
        $this->pageNumberFormat = $format;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function setOptions(array $options): static
    {
        $this->engineOptions = array_merge($this->engineOptions, $options);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->engineOptions;
    }

    public function setExtraCss(string $css): static
    {
        $this->extraCss = $css;

        return $this;
    }

    public function getExtraCss(): string
    {
        return $this->extraCss;
    }

    public function setHtmlLang(string $lang): static
    {
        $this->htmlLang = $lang;

        return $this;
    }

    public function getHtml(): string
    {
        return $this->html;
    }

    /**
     * Write a pre-rendered binary PDF to a local path with path validation.
     */
    public function writeBinary(string $path, string $binary): bool
    {
        $safePath = $this->securityManager->validateLocalPath($path);
        $directory = dirname($safePath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return file_put_contents($safePath, $binary) !== false;
    }

    /**
     * Prepare HTML by injecting fonts, checking UTF-8, and detecting direction.
     *
     * @return array{
     *     html: string,
     *     detected_direction: string,
     *     detected_scripts: array<string, int>,
     *     dominant_script: string,
     *     resolved_fonts: array<string>
     * }
     */
    protected function getPreparedContent(): array
    {
        $prepared = $this->pdfRenderer->prepareHtml(
            html: $this->html,
            primaryFont: $this->primaryFont,
            fallbackFonts: $this->fallbackFonts,
            direction: $this->direction,
            autoInjectCss: true,
            htmlLang: $this->htmlLang,
            extraCss: $this->extraCss
        );

        return $prepared;
    }

    public function save(string $path): bool
    {
        return $this->writeBinary($path, $this->output());
    }

    public function store(string $path, ?string $disk = null): bool
    {
        $content = $this->output();

        return Storage::disk($disk)->put($path, $content);
    }

    public function stream(string $filename = 'document.pdf'): Response
    {
        $content = $this->output();

        return HttpHeaderHelper::makeStreamResponse($content, $filename);
    }

    public function download(string $filename = 'document.pdf'): Response
    {
        $content = $this->output();

        return HttpHeaderHelper::makeDownloadResponse($content, $filename);
    }
}
