<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Mail\Attachment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use ImranDev\UnicodePdf\Contracts\PdfEngine;
use ImranDev\UnicodePdf\Enums\Direction;
use ImranDev\UnicodePdf\Enums\Engine;
use ImranDev\UnicodePdf\Enums\Orientation;
use ImranDev\UnicodePdf\Enums\PaperSize;
use ImranDev\UnicodePdf\Enums\Preset;
use ImranDev\UnicodePdf\Events\PdfFailed;
use ImranDev\UnicodePdf\Events\PdfGenerated;
use ImranDev\UnicodePdf\Events\PdfGenerating;
use ImranDev\UnicodePdf\Exceptions\PresetNotFoundException;
use ImranDev\UnicodePdf\Exceptions\UnicodePdfException;
use ImranDev\UnicodePdf\Fonts\FontDetector;
use ImranDev\UnicodePdf\Fonts\FontManager;
use ImranDev\UnicodePdf\Jobs\GeneratePdfJob;
use ImranDev\UnicodePdf\Services\HttpHeaderHelper;
use ImranDev\UnicodePdf\Testing\UnicodePdfFake;
use ImranDev\UnicodePdf\Unicode\LocaleMapper;
use ImranDev\UnicodePdf\Unicode\UnicodeNormalizer;
use ImranDev\UnicodePdf\Unicode\Utf8Validator;
use Stringable;
use Symfony\Component\HttpFoundation\Response as BaseResponse;
use Throwable;

class UnicodePdfDocument implements Responsable, Stringable
{
    use Conditionable;
    use Macroable;
    use Tappable;

    protected ?string $presetName = null;

    protected ?string $locale = null;

    protected bool $debugMode = false;

    protected string $filename = 'document.pdf';

    protected bool $downloadOnResponse = true;

    protected ?int $cacheTtl = null;

    protected ?string $cacheStore = null;

    /**
     * @var array<string, mixed>
     */
    protected array $snapshot = [];

    public function __construct(
        protected PdfEngine $engine,
        protected FontManager $fontManager,
        protected Utf8Validator $utf8Validator = new Utf8Validator,
        protected UnicodeNormalizer $normalizer = new UnicodeNormalizer,
        protected FontDetector $fontDetector = new FontDetector,
        protected ?UnicodePdfManager $manager = null
    ) {}

    /**
     * Switch the underlying PDF engine.
     */
    public function engine(string|Engine $name): static
    {
        if ($this->manager) {
            $this->engine = $this->manager->driver($name instanceof Engine ? $name->value : $name);
        }

        $this->snapshot['engine'] = $name instanceof Engine ? $name->value : $name;

        return $this;
    }

    /**
     * Get underlying PDF engine adapter.
     */
    public function getEngine(): PdfEngine
    {
        return $this->engine;
    }

    /**
     * Load raw HTML content.
     */
    public function loadHtml(string $html): static
    {
        $this->engine->loadHtml($html);

        return $this;
    }

    /**
     * Alias for loadHtml.
     */
    public function html(string $html): static
    {
        return $this->loadHtml($html);
    }

    /**
     * Load HTML from a local file path.
     */
    public function loadFile(string $path): static
    {
        if (! is_readable($path)) {
            throw new UnicodePdfException("HTML file is not readable: {$path}");
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new UnicodePdfException("Unable to read HTML file: {$path}");
        }

        return $this->loadHtml($contents);
    }

    /**
     * Load a Blade view template with data.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $mergeData
     */
    public function loadView(string $view, array $data = [], array $mergeData = []): static
    {
        $this->engine->loadView($view, $data, $mergeData);

        return $this;
    }

    /**
     * Alias for loadView.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $mergeData
     */
    public function view(string $view, array $data = [], array $mergeData = []): static
    {
        return $this->loadView($view, $data, $mergeData);
    }

    /**
     * Set primary font family.
     */
    public function font(string $font): static
    {
        $this->engine->setFont($font);
        $this->snapshot['font'] = $font;

        return $this;
    }

    /**
     * Set fallback fonts stack.
     *
     * @param  array<string>  $fonts
     */
    public function fallback(array $fonts): static
    {
        $this->engine->setFallbackFonts($fonts);
        $this->snapshot['fallback'] = $fonts;

        return $this;
    }

    /**
     * Apply a predefined script & typography preset.
     */
    public function preset(string|Preset $name): static
    {
        $presetName = $name instanceof Preset ? $name->value : $name;
        $preset = $this->fontManager->getPreset($presetName);

        if (! $preset) {
            throw PresetNotFoundException::preset($presetName);
        }

        $this->presetName = $presetName;
        $this->snapshot['preset'] = $presetName;
        $this->font($preset->getDefaultFont());
        $this->fallback($preset->getFallbackFonts());
        $this->direction($preset->getDirection());

        return $this;
    }

    /**
     * Set locale and apply locale-specific defaults.
     */
    public function locale(string $locale): static
    {
        $this->locale = $locale;
        $this->snapshot['locale'] = $locale;
        $info = LocaleMapper::get($locale);

        if ($info) {
            $this->font($info['font']);
            $this->direction($info['direction']);
        }

        if (method_exists($this->engine, 'setHtmlLang')) {
            $this->engine->setHtmlLang(str_replace('_', '-', $locale));
        }

        return $this;
    }

    /**
     * Set document text direction ('auto' | 'ltr' | 'rtl').
     */
    public function direction(string|Direction $direction): static
    {
        $value = $direction instanceof Direction ? $direction->value : $direction;
        $this->engine->setDirection($value);
        $this->snapshot['direction'] = $value;

        return $this;
    }

    /**
     * Enable/disable bidirectional text processing.
     */
    public function bidi(bool $enabled = true): static
    {
        $this->engine->setBidi($enabled);

        return $this;
    }

    /**
     * Set paper size and orientation.
     *
     * @param  string|array<int|float>|PaperSize  $paper
     */
    public function setPaper(string|array|PaperSize $paper, string|Orientation $orientation = 'portrait'): static
    {
        $paperValue = $paper instanceof PaperSize ? $paper->value : $paper;
        $orientationValue = $orientation instanceof Orientation ? $orientation->value : $orientation;

        $this->engine->setPaper($paperValue, $orientationValue);
        $this->snapshot['paper'] = $paperValue;
        $this->snapshot['orientation'] = $orientationValue;

        return $this;
    }

    /**
     * Alias for setPaper.
     *
     * @param  string|array<int|float>|PaperSize  $paper
     */
    public function paper(string|array|PaperSize $paper, string|Orientation $orientation = 'portrait'): static
    {
        return $this->setPaper($paper, $orientation);
    }

    public function a4(string|Orientation $orientation = 'portrait'): static
    {
        return $this->setPaper(PaperSize::A4, $orientation);
    }

    public function letter(string|Orientation $orientation = 'portrait'): static
    {
        return $this->setPaper(PaperSize::Letter, $orientation);
    }

    public function legal(string|Orientation $orientation = 'portrait'): static
    {
        return $this->setPaper(PaperSize::Legal, $orientation);
    }

    /**
     * Set document orientation ('portrait' | 'landscape').
     */
    public function orientation(string|Orientation $orientation): static
    {
        $value = $orientation instanceof Orientation ? $orientation->value : $orientation;
        $this->engine->setOrientation($value);
        $this->snapshot['orientation'] = $value;

        return $this;
    }

    public function landscape(): static
    {
        return $this->orientation(Orientation::Landscape);
    }

    public function portrait(): static
    {
        return $this->orientation(Orientation::Portrait);
    }

    /**
     * Set page margins.
     */
    public function setMargins(
        int|float $top = 10,
        int|float $right = 10,
        int|float $bottom = 10,
        int|float $left = 10,
        string $unit = 'mm'
    ): static {
        $this->engine->setMargins($top, $right, $bottom, $left, $unit);

        return $this;
    }

    /**
     * Fluent alias for setMargins.
     */
    public function margin(
        int|float $top = 10,
        int|float $right = 10,
        int|float $bottom = 10,
        int|float $left = 10,
        string $unit = 'mm'
    ): static {
        return $this->setMargins($top, $right, $bottom, $left, $unit);
    }

    /**
     * Set document metadata.
     *
     * @param  array<string, string>  $metadata
     */
    public function metadata(array $metadata): static
    {
        $this->engine->setMetadata($metadata);
        $this->snapshot['metadata'] = array_merge($this->snapshot['metadata'] ?? [], $metadata);

        return $this;
    }

    public function title(string $title): static
    {
        return $this->metadata(['title' => $title]);
    }

    public function author(string $author): static
    {
        return $this->metadata(['author' => $author]);
    }

    public function subject(string $subject): static
    {
        return $this->metadata(['subject' => $subject]);
    }

    public function keywords(string $keywords): static
    {
        return $this->metadata(['keywords' => $keywords]);
    }

    public function creator(string $creator): static
    {
        return $this->metadata(['creator' => $creator]);
    }

    /**
     * Add watermark.
     */
    public function watermark(string $text, float $opacity = 0.2): static
    {
        $this->engine->setWatermark($text, $opacity);

        return $this;
    }

    /**
     * Set password protection & permissions.
     *
     * @param  array<string, mixed>  $options
     */
    public function protect(array $options): static
    {
        $this->engine->protect($options);

        return $this;
    }

    /**
     * Encrypt the PDF with a user and optional owner password.
     *
     * @param  array<string, mixed>  $permissions
     */
    public function encrypt(string $userPassword, ?string $ownerPassword = null, array $permissions = []): static
    {
        return $this->protect(array_filter([
            'user_password' => $userPassword,
            'owner_password' => $ownerPassword,
            'permissions' => $permissions ?: null,
        ]));
    }

    /**
     * Pass extra engine-specific options.
     *
     * @param  array<string, mixed>  $options
     */
    public function options(array $options): static
    {
        if (method_exists($this->engine, 'setOptions')) {
            $this->engine->setOptions($options);
        }

        return $this;
    }

    public function option(string $key, mixed $value): static
    {
        return $this->options([$key => $value]);
    }

    /**
     * Inject extra CSS into the prepared HTML. Multiple calls append.
     */
    public function css(string $css): static
    {
        if (method_exists($this->engine, 'setExtraCss')) {
            $existing = method_exists($this->engine, 'getExtraCss')
                ? $this->engine->getExtraCss()
                : '';
            $this->engine->setExtraCss(trim($existing."\n".$css));
        }

        return $this;
    }

    /**
     * Set the default body font size (points, or a CSS size like "16px" / "1.2em").
     */
    public function fontSize(float|int|string $size): static
    {
        $cssSize = is_numeric($size) ? $size.'pt' : (string) $size;

        return $this->css("body, p, td, th, div, li, span { font-size: {$cssSize}; }");
    }

    /**
     * Set HTML lang attribute.
     */
    public function lang(string $lang): static
    {
        if (method_exists($this->engine, 'setHtmlLang')) {
            $this->engine->setHtmlLang($lang);
        }

        return $this;
    }

    /**
     * Set header template.
     *
     * @param  array<string, mixed>  $data
     */
    public function header(string $html, array $data = []): static
    {
        $this->engine->setHeader($html, $data);

        return $this;
    }

    /**
     * Set footer template.
     *
     * @param  array<string, mixed>  $data
     */
    public function footer(string $html, array $data = []): static
    {
        $this->engine->setFooter($html, $data);

        return $this;
    }

    /**
     * Configure page numbering.
     */
    public function pageNumbers(string $format = '{PAGE_NUM} / {PAGE_COUNT}'): static
    {
        $this->engine->setPageNumbers($format);

        return $this;
    }

    /**
     * Set the default filename used by download, stream, and toResponse.
     */
    public function name(string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Cache the rendered binary for the given TTL (seconds).
     */
    public function cache(?int $seconds = null, ?string $store = null): static
    {
        $this->cacheTtl = $seconds ?? (int) config('unicode-pdf.cache.ttl', 3600);
        $this->cacheStore = $store;

        return $this;
    }

    /**
     * Disable output caching for this document.
     */
    public function withoutCache(): static
    {
        $this->cacheTtl = null;

        return $this;
    }

    /**
     * Toggle debug mode.
     */
    public function debug(bool $enable = true): static
    {
        $this->debugMode = $enable;

        return $this;
    }

    /**
     * Check if engine supports a capability.
     */
    public function supports(string $capability): bool
    {
        return $this->engine->supports($capability);
    }

    /**
     * Generate and return raw binary PDF output.
     */
    public function output(): string
    {
        if ($this->cacheTtl !== null && $this->cacheTtl > 0) {
            return $this->cacheStore($this->cacheKey(), $this->cacheTtl, fn () => $this->renderOutput());
        }

        return $this->renderOutput();
    }

    /**
     * Save PDF to local file path.
     */
    public function save(string $path): bool
    {
        $binary = $this->output();

        if (method_exists($this->engine, 'writeBinary')) {
            return $this->engine->writeBinary($path, $binary);
        }

        return $this->engine->save($path);
    }

    /**
     * Save PDF to a Laravel storage disk.
     */
    public function store(string $path, ?string $disk = null): bool
    {
        $binary = $this->output();

        return Storage::disk($disk)->put($path, $binary);
    }

    /**
     * Store as filename on disk.
     */
    public function storeAs(string $path, string $name, ?string $disk = null): bool
    {
        $fullPath = rtrim($path, '/\\').'/'.ltrim($name, '/\\');

        return $this->store($fullPath, $disk);
    }

    /**
     * Queue PDF generation to a storage path.
     */
    public function queue(string $path, ?string $disk = null): mixed
    {
        $html = method_exists($this->engine, 'getHtml') ? $this->engine->getHtml() : '';

        return GeneratePdfJob::dispatch($html, $path, $disk, $this->snapshot);
    }

    /**
     * Dispatch generation after the HTTP response (Laravel 11+ defer).
     */
    public function defer(string $path, ?string $disk = null): static
    {
        $html = method_exists($this->engine, 'getHtml') ? $this->engine->getHtml() : '';
        $snapshot = $this->snapshot;
        $engineName = $this->snapshot['engine'] ?? null;

        $callback = function () use ($html, $path, $disk, $snapshot, $engineName): void {
            $manager = $this->manager ?? app('unicode-pdf');
            $document = $manager->createDocument(is_string($engineName) ? $engineName : null);

            if (isset($snapshot['preset']) && is_string($snapshot['preset'])) {
                $document->preset($snapshot['preset']);
            }

            $document->loadHtml($html)->store($path, $disk);
        };

        if (function_exists('defer')) {
            defer($callback);

            return $this;
        }

        $callback();

        return $this;
    }

    /**
     * Stream PDF in browser.
     */
    public function stream(string $filename = 'document.pdf'): Response
    {
        $binary = $this->output();
        $this->recordFake('stream', $filename, $binary);

        return HttpHeaderHelper::makeStreamResponse($binary, $filename);
    }

    /**
     * Alias for stream (inline Content-Disposition).
     */
    public function inline(string $filename = 'document.pdf'): Response
    {
        $this->downloadOnResponse = false;

        return $this->stream($filename);
    }

    /**
     * Force download PDF file.
     */
    public function download(string $filename = 'document.pdf'): Response
    {
        $binary = $this->output();
        $this->recordFake('download', $filename, $binary);

        return HttpHeaderHelper::makeDownloadResponse($binary, $filename);
    }

    /**
     * Base64-encoded PDF binary.
     */
    public function base64(): string
    {
        return base64_encode($this->output());
    }

    /**
     * Data URI for embedding in HTML/JSON.
     */
    public function dataUri(): string
    {
        return 'data:application/pdf;base64,'.$this->base64();
    }

    /**
     * Laravel mail attachment (requires illuminate/mail).
     */
    public function toMailAttachment(?string $filename = null): mixed
    {
        if (! class_exists(Attachment::class)) {
            throw new UnicodePdfException('Mail attachments require the illuminate/mail component.');
        }

        $name = $filename ?: $this->filename;

        return Attachment::fromData(fn () => $this->output(), $name)
            ->withMime('application/pdf');
    }

    /**
     * Convert the PDF into an HTTP response (download by default).
     *
     * @param  Request  $request
     */
    public function toResponse($request): BaseResponse
    {
        return $this->downloadOnResponse
            ? $this->download($this->filename)
            : $this->stream($this->filename);
    }

    public function __toString(): string
    {
        return $this->output();
    }

    /**
     * Snapshot of fluent options for queued jobs.
     *
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        return $this->snapshot;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getPresetName(): ?string
    {
        return $this->presetName;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function isDebug(): bool
    {
        return $this->debugMode;
    }

    protected function renderOutput(): string
    {
        $started = microtime(true);

        try {
            $this->dispatchEvent(new PdfGenerating($this));
            $binary = $this->engine->output();
            $this->dispatchEvent(new PdfGenerated($this, $binary, (microtime(true) - $started) * 1000));
            $this->recordFake('output', $this->filename, $binary);

            return $binary;
        } catch (Throwable $exception) {
            $this->dispatchEvent(new PdfFailed($this, $exception));

            throw $exception;
        }
    }

    protected function cacheKey(): string
    {
        $html = method_exists($this->engine, 'getHtml') ? $this->engine->getHtml() : '';

        return 'unicode-pdf:'.sha1($html.serialize($this->snapshot).$this->engine->getName());
    }

    /**
     * @param  callable(): string  $callback
     */
    protected function cacheStore(string $key, int $ttl, callable $callback): string
    {
        try {
            $repository = $this->cacheStore
                ? Cache::store($this->cacheStore)
                : Cache::store();

            return (string) $repository->remember($key, $ttl, $callback);
        } catch (Throwable) {
            return $callback();
        }
    }

    protected function dispatchEvent(object $event): void
    {
        try {
            Event::dispatch($event);
        } catch (Throwable) {
            // Events are optional when the container is not booted.
        }
    }

    protected function recordFake(string $action, ?string $filename = null, ?string $binary = null): void
    {
        if (! $this->manager instanceof UnicodePdfFake) {
            return;
        }

        $this->manager->record($this, $binary ?? '', $action, $filename);
    }
}
