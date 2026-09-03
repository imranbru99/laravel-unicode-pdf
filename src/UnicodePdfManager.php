<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf;

use Closure;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use ImranDev\UnicodePdf\Contracts\PdfEngine;
use ImranDev\UnicodePdf\Engines\BrowsershotEngine;
use ImranDev\UnicodePdf\Engines\DompdfEngine;
use ImranDev\UnicodePdf\Engines\MpdfEngine;
use ImranDev\UnicodePdf\Engines\NativeEngine;
use ImranDev\UnicodePdf\Engines\NullEngine;
use ImranDev\UnicodePdf\Engines\TcpdfEngine;
use ImranDev\UnicodePdf\Enums\Engine;
use ImranDev\UnicodePdf\Enums\Preset;
use ImranDev\UnicodePdf\Exceptions\PresetNotFoundException;
use ImranDev\UnicodePdf\Exceptions\UnsupportedEngineException;
use ImranDev\UnicodePdf\Fonts\FontDetector;
use ImranDev\UnicodePdf\Fonts\FontManager;
use ImranDev\UnicodePdf\Services\SecurityManager;
use ImranDev\UnicodePdf\Support\NumeralConverter;
use ImranDev\UnicodePdf\Testing\UnicodePdfFake;
use ImranDev\UnicodePdf\Unicode\DirectionDetector;
use ImranDev\UnicodePdf\Unicode\ScriptDetector;
use ImranDev\UnicodePdf\Unicode\UnicodeNormalizer;
use ImranDev\UnicodePdf\Unicode\Utf8Validator;

class UnicodePdfManager
{
    use Conditionable;
    use Macroable {
        __call as macroCall;
    }
    use Tappable;

    /**
     * @var array<string, Closure>
     */
    protected array $customCreators = [];

    /**
     * @var array<string, PdfEngine>
     */
    protected array $resolvedEngines = [];

    protected FontManager $fontManager;

    protected Utf8Validator $utf8Validator;

    protected UnicodeNormalizer $normalizer;

    protected ScriptDetector $scriptDetector;

    protected DirectionDetector $directionDetector;

    protected FontDetector $fontDetector;

    protected SecurityManager $securityManager;

    public function __construct(
        protected Container $container,
        protected ConfigRepository $config
    ) {
        $this->fontManager = $container->bound(FontManager::class)
            ? $container->make(FontManager::class)
            : new FontManager($config);
        $this->utf8Validator = new Utf8Validator;
        $this->normalizer = new UnicodeNormalizer;
        $this->scriptDetector = new ScriptDetector;
        $this->directionDetector = new DirectionDetector;
        $this->fontDetector = new FontDetector($this->scriptDetector);

        $this->securityManager = new SecurityManager(
            allowRemoteImages: (bool) $config->get('unicode-pdf.security.allow_remote_images', false),
            allowRemoteFonts: (bool) $config->get('unicode-pdf.security.allow_remote_fonts', false),
            allowedRemoteHosts: (array) $config->get('unicode-pdf.security.allowed_remote_hosts', []),
            allowedLocalPaths: (array) $config->get('unicode-pdf.security.allowed_local_paths', [])
        );
    }

    /**
     * Swap the manager with a fake for tests.
     */
    public function fake(): UnicodePdfFake
    {
        $fake = new UnicodePdfFake($this->container, $this->config);

        foreach ($this->customCreators as $driver => $callback) {
            $fake->extend($driver, $callback);
        }

        $this->container->instance('unicode-pdf', $fake);
        $this->container->instance(UnicodePdfManager::class, $fake);

        return $fake;
    }

    /**
     * Create a new document instance with the default or specified engine.
     */
    public function createDocument(string|Engine|null $engine = null): UnicodePdfDocument
    {
        $engineName = $engine instanceof Engine ? $engine->value : $engine;
        $engineInstance = $this->driver($engineName);

        $defaultFont = (string) $this->config->get('unicode-pdf.default_font', 'Noto Sans');
        $fallbackFonts = (array) $this->config->get('unicode-pdf.fallback_fonts', []);
        $direction = (string) $this->config->get('unicode-pdf.direction', 'auto');
        $paper = $this->config->get('unicode-pdf.paper', 'a4');
        $orientation = (string) $this->config->get('unicode-pdf.orientation', 'portrait');

        $margins = (array) $this->config->get('unicode-pdf.margins', [
            'top' => 10,
            'right' => 10,
            'bottom' => 10,
            'left' => 10,
            'unit' => 'mm',
        ]);

        $doc = new UnicodePdfDocument(
            engine: $engineInstance,
            fontManager: $this->fontManager,
            utf8Validator: $this->utf8Validator,
            normalizer: $this->normalizer,
            fontDetector: $this->fontDetector,
            manager: $this
        );

        $doc->font($defaultFont)
            ->fallback($fallbackFonts)
            ->direction($direction)
            ->setPaper($paper, $orientation)
            ->setMargins(
                $margins['top'] ?? 10,
                $margins['right'] ?? 10,
                $margins['bottom'] ?? 10,
                $margins['left'] ?? 10,
                $margins['unit'] ?? 'mm'
            );

        return $doc;
    }

    /**
     * Create a new document using the specified engine (fluent entrypoint).
     */
    public function engine(string|Engine|null $name = null): UnicodePdfDocument
    {
        return $this->createDocument($name);
    }

    /**
     * Create a document from a named config profile.
     */
    public function profile(string $name): UnicodePdfDocument
    {
        $profiles = (array) $this->config->get('unicode-pdf.profiles', []);

        if (! isset($profiles[$name]) || ! is_array($profiles[$name])) {
            throw PresetNotFoundException::profile($name);
        }

        $profile = $profiles[$name];
        $engine = isset($profile['engine']) && is_string($profile['engine']) ? $profile['engine'] : null;
        $doc = $this->createDocument($engine);

        if (isset($profile['preset']) && is_string($profile['preset'])) {
            $doc->preset($profile['preset']);
        }

        if (isset($profile['font']) && is_string($profile['font'])) {
            $doc->font($profile['font']);
        }

        if (isset($profile['fallback']) && is_array($profile['fallback'])) {
            $doc->fallback($profile['fallback']);
        }

        if (isset($profile['direction']) && is_string($profile['direction'])) {
            $doc->direction($profile['direction']);
        }

        if (isset($profile['paper'])) {
            $doc->setPaper(
                $profile['paper'],
                is_string($profile['orientation'] ?? null) ? $profile['orientation'] : 'portrait'
            );
        } elseif (isset($profile['orientation']) && is_string($profile['orientation'])) {
            $doc->orientation($profile['orientation']);
        }

        if (isset($profile['locale']) && is_string($profile['locale'])) {
            $doc->locale($profile['locale']);
        }

        if (isset($profile['metadata']) && is_array($profile['metadata'])) {
            $doc->metadata($profile['metadata']);
        }

        return $doc;
    }

    /**
     * Get a raw PDF engine driver instance by name or default.
     */
    public function driver(?string $name = null): PdfEngine
    {
        $name = $name ?: $this->getDefaultEngine();

        if (isset($this->customCreators[$name])) {
            return $this->customCreators[$name]($this->container, $this->fontManager, $this->securityManager);
        }

        return match (strtolower($name)) {
            'native' => new NativeEngine(
                viewFactory: $this->container->bound('view') ? $this->container->make('view') : null,
                fontManager: $this->fontManager,
                securityManager: $this->securityManager
            ),
            'dompdf' => new DompdfEngine(
                viewFactory: $this->container->bound('view') ? $this->container->make('view') : null,
                fontManager: $this->fontManager,
                securityManager: $this->securityManager
            ),
            'mpdf' => new MpdfEngine(
                viewFactory: $this->container->bound('view') ? $this->container->make('view') : null,
                fontManager: $this->fontManager,
                securityManager: $this->securityManager
            ),
            'tcpdf' => new TcpdfEngine(
                viewFactory: $this->container->bound('view') ? $this->container->make('view') : null,
                fontManager: $this->fontManager,
                securityManager: $this->securityManager
            ),
            'browsershot' => new BrowsershotEngine(
                viewFactory: $this->container->bound('view') ? $this->container->make('view') : null,
                fontManager: $this->fontManager,
                securityManager: $this->securityManager
            ),
            'null' => new NullEngine(
                viewFactory: $this->container->bound('view') ? $this->container->make('view') : null,
                fontManager: $this->fontManager,
                securityManager: $this->securityManager
            ),
            default => throw UnsupportedEngineException::notFound($name),
        };
    }

    /**
     * Register a custom engine creator closure.
     */
    public function extend(string $driver, Closure $callback): static
    {
        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /**
     * Get default engine name from config.
     */
    public function getDefaultEngine(): string
    {
        return (string) $this->config->get('unicode-pdf.engine', 'native');
    }

    /**
     * Load raw HTML content into a new document.
     */
    public function loadHtml(string $html): UnicodePdfDocument
    {
        return $this->createDocument()->loadHtml($html);
    }

    /**
     * Load HTML from a local file.
     */
    public function loadFile(string $path): UnicodePdfDocument
    {
        return $this->createDocument()->loadFile($path);
    }

    /**
     * Load Blade view template into a new document.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $mergeData
     */
    public function loadView(string $view, array $data = [], array $mergeData = []): UnicodePdfDocument
    {
        return $this->createDocument()->loadView($view, $data, $mergeData);
    }

    /**
     * Alias for loadView.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $mergeData
     */
    public function view(string $view, array $data = [], array $mergeData = []): UnicodePdfDocument
    {
        return $this->loadView($view, $data, $mergeData);
    }

    /**
     * Create a document with a preset applied.
     */
    public function preset(string|Preset $name): UnicodePdfDocument
    {
        return $this->createDocument()->preset($name);
    }

    /**
     * Register a custom font family definition.
     *
     * @param  array<string, mixed>  $fontDefinition
     */
    public function registerFont(array $fontDefinition): void
    {
        $this->fontManager->register($fontDefinition);
    }

    /**
     * Get FontManager instance.
     */
    public function getFontManager(): FontManager
    {
        return $this->fontManager;
    }

    /**
     * Check if default engine supports a capability.
     */
    public function supports(string $capability): bool
    {
        try {
            return $this->driver()->supports($capability);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Validate UTF-8 string.
     */
    public function validateUtf8(string $text): bool
    {
        return $this->utf8Validator->validate($text);
    }

    /**
     * Normalize Unicode string.
     */
    public function normalize(string $text, string $form = 'NFC'): string
    {
        return $this->normalizer->normalize($text, $form);
    }

    /**
     * Convert Western digits to native numerals for a locale.
     */
    public function numerals(string $value, string $locale): string
    {
        return NumeralConverter::convert($value, $locale);
    }

    /**
     * Detect Unicode scripts in text.
     *
     * @return array<string, int>
     */
    public function detectScripts(string $text): array
    {
        return $this->scriptDetector->detect($text);
    }

    /**
     * Detect text direction.
     */
    public function detectDirection(string $text): string
    {
        return $this->directionDetector->detect($text);
    }

    /**
     * Diagnose text for missing glyphs and suggested fonts.
     *
     * @return array<string, mixed>
     */
    public function checkGlyphs(string $text, ?string $font = null): array
    {
        $font = $font ?: (string) $this->config->get('unicode-pdf.default_font', 'Noto Sans');

        return $this->fontDetector->diagnose($text, $font, array_keys($this->fontManager->all()));
    }

    /**
     * Clear font metadata and PDF render cache files.
     */
    public function clearCache(): bool
    {
        $cacheDir = (string) $this->config->get('unicode-pdf.performance.cache_path', storage_path('app/unicode-pdf/cache'));
        if ($cacheDir === '' || $cacheDir === '0') {
            $cacheDir = (string) $this->config->get('unicode-pdf.cache.path', storage_path('app/unicode-pdf/cache'));
        }

        if (is_dir($cacheDir)) {
            $files = glob($cacheDir.'/*');
            if ($files) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        @unlink($file);
                    }
                }
            }
        }

        $fontCache = (string) $this->config->get('unicode-pdf.font_cache', storage_path('app/unicode-pdf/cache/fonts'));
        if (is_dir($fontCache)) {
            $files = glob($fontCache.'/*');
            if ($files) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        @unlink($file);
                    }
                }
            }
        }

        return true;
    }

    /**
     * Proxy dynamic calls to macros or a new document instance.
     *
     * @param  array<mixed>  $arguments
     */
    public function __call(string $method, array $arguments): mixed
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $arguments);
        }

        return $this->createDocument()->$method(...$arguments);
    }
}
