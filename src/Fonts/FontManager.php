<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Fonts;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use ImranDev\UnicodePdf\Contracts\FontRepositoryInterface;
use ImranDev\UnicodePdf\Exceptions\FontRegistrationException;
use ImranDev\UnicodePdf\Fonts\Presets\ArabicPreset;
use ImranDev\UnicodePdf\Fonts\Presets\BengaliPreset;
use ImranDev\UnicodePdf\Fonts\Presets\CjkPreset;
use ImranDev\UnicodePdf\Fonts\Presets\IndianPreset;
use ImranDev\UnicodePdf\Fonts\Presets\PresetInterface;
use ImranDev\UnicodePdf\Fonts\Presets\ScriptPreset;
use ImranDev\UnicodePdf\Fonts\Presets\UniversalPreset;

class FontManager implements FontRepositoryInterface
{
    /**
     * @var array<string, array<string, mixed>>
     */
    protected array $fonts = [];

    /**
     * @var array<string, PresetInterface>
     */
    protected array $presets = [];

    protected FontResolver $resolver;

    protected FontDetector $detector;

    protected ?string $cachePath = null;

    public function __construct(
        protected ?ConfigRepository $config = null,
        ?FontResolver $resolver = null,
        ?FontDetector $detector = null
    ) {
        $this->resolver = $resolver ?? new FontResolver;
        $this->detector = $detector ?? new FontDetector;

        $this->registerDefaultPresets();
        $this->loadConfiguredFonts();
    }

    /**
     * Register default script presets.
     */
    protected function registerDefaultPresets(): void
    {
        $this->presets['bengali'] = new BengaliPreset;
        $this->presets['arabic'] = new ArabicPreset;
        $this->presets['indian'] = new IndianPreset;
        $this->presets['cjk'] = new CjkPreset;
        $this->presets['universal'] = new UniversalPreset;

        foreach ($this->scriptPresets() as $preset) {
            $this->presets[$preset->getName()] = $preset;
        }

        $this->presets['bn'] = $this->presets['bengali'];
        $this->presets['hi'] = $this->presets['indian'];
        $this->presets['zh'] = $this->presets['cjk'];
        $this->presets['fa'] = $this->presets['persian'];
        $this->presets['ur'] = $this->presets['urdu'];
        $this->presets['ja'] = $this->presets['japanese'];
        $this->presets['ko'] = $this->presets['korean'];
        $this->presets['he'] = $this->presets['hebrew'];
        $this->presets['th'] = $this->presets['thai'];
        $this->presets['el'] = $this->presets['greek'];
        $this->presets['ru'] = $this->presets['cyrillic'];
        $this->presets['am'] = $this->presets['ethiopic'];
        $this->presets['km'] = $this->presets['khmer'];
        $this->presets['my'] = $this->presets['myanmar'];
        $this->presets['si'] = $this->presets['sinhala'];
        $this->presets['ta'] = $this->presets['tamil'];
        $this->presets['vi'] = $this->presets['vietnamese'];
        $this->presets['en'] = $this->presets['latin'];
    }

    /**
     * Additional script presets registered as ScriptPreset value objects.
     *
     * @return array<int, ScriptPreset>
     */
    protected function scriptPresets(): array
    {
        return [
            new ScriptPreset('thai', 'Noto Sans Thai', ['Noto Sans Thai', 'Noto Sans'], 'ltr', ['script' => 'Thai', 'complex_shaping' => true]),
            new ScriptPreset('hebrew', 'Noto Sans Hebrew', ['Noto Sans Hebrew', 'Noto Sans'], 'rtl', ['script' => 'Hebrew', 'bidi' => true, 'rtl' => true]),
            new ScriptPreset('persian', 'Noto Sans Arabic', ['Noto Sans Arabic', 'Noto Naskh Arabic', 'Noto Sans'], 'rtl', ['script' => 'Arabic', 'bidi' => true, 'rtl' => true]),
            new ScriptPreset('urdu', 'Noto Nastaliq Urdu', ['Noto Nastaliq Urdu', 'Noto Sans Arabic', 'Noto Sans'], 'rtl', ['script' => 'Arabic', 'bidi' => true, 'rtl' => true]),
            new ScriptPreset('tamil', 'Noto Sans Tamil', ['Noto Sans Tamil', 'Noto Sans'], 'ltr', ['script' => 'Tamil', 'complex_shaping' => true]),
            new ScriptPreset('korean', 'Noto Sans CJK KR', ['Noto Sans CJK KR', 'Noto Sans'], 'ltr', ['script' => 'Korean']),
            new ScriptPreset('japanese', 'Noto Sans CJK JP', ['Noto Sans CJK JP', 'Noto Sans'], 'ltr', ['script' => 'Japanese']),
            new ScriptPreset('vietnamese', 'Noto Sans', ['Noto Sans', 'Noto Sans Vietnamese'], 'ltr', ['script' => 'Latin']),
            new ScriptPreset('greek', 'Noto Sans', ['Noto Sans', 'Noto Serif'], 'ltr', ['script' => 'Greek']),
            new ScriptPreset('cyrillic', 'Noto Sans', ['Noto Sans', 'Noto Serif'], 'ltr', ['script' => 'Cyrillic']),
            new ScriptPreset('ethiopic', 'Noto Sans Ethiopic', ['Noto Sans Ethiopic', 'Noto Sans'], 'ltr', ['script' => 'Ethiopic']),
            new ScriptPreset('khmer', 'Noto Sans Khmer', ['Noto Sans Khmer', 'Noto Sans'], 'ltr', ['script' => 'Khmer', 'complex_shaping' => true]),
            new ScriptPreset('myanmar', 'Noto Sans Myanmar', ['Noto Sans Myanmar', 'Noto Sans'], 'ltr', ['script' => 'Myanmar', 'complex_shaping' => true]),
            new ScriptPreset('sinhala', 'Noto Sans Sinhala', ['Noto Sans Sinhala', 'Noto Sans'], 'ltr', ['script' => 'Sinhala', 'complex_shaping' => true]),
            new ScriptPreset('latin', 'Noto Sans', ['Noto Sans', 'Noto Serif'], 'ltr', ['script' => 'Latin']),
        ];
    }

    /**
     * Get all registered preset names.
     *
     * @return list<string>
     */
    public function presetNames(): array
    {
        return array_keys($this->presets);
    }

    /**
     * Load fonts defined in config.
     */
    protected function loadConfiguredFonts(): void
    {
        if (! $this->config) {
            return;
        }

        $configFonts = $this->config->get('unicode-pdf.fonts', []);
        foreach ($configFonts as $family => $definition) {
            if (is_array($definition)) {
                $definition['family'] = $family;
                $this->register($definition);
            }
        }
    }

    /**
     * Register a font family definition.
     *
     * @param  array<string, mixed>  $fontDefinition
     */
    public function register(array $fontDefinition): void
    {
        $family = isset($fontDefinition['family']) && is_string($fontDefinition['family']) ? $fontDefinition['family'] : null;
        if (! $family) {
            throw new FontRegistrationException('Font definition must contain a "family" name.');
        }

        foreach (['regular', 'bold', 'italic', 'bold_italic'] as $style) {
            if (! empty($fontDefinition[$style]) && ! file_exists($fontDefinition[$style])) {
                // If it's a relative path in package or storage, verify
                if (! file_exists(base_path($fontDefinition[$style]))) {
                    // Soft fallback: retain if exists, otherwise throw if strict
                }
            }
        }

        $this->fonts[$family] = $fontDefinition;
    }

    /**
     * Check if a font family is registered.
     */
    public function has(string $family): bool
    {
        return isset($this->fonts[$family]);
    }

    /**
     * Get font definition by family name.
     */
    public function get(string $family): ?array
    {
        return $this->fonts[$family] ?? null;
    }

    /**
     * Get all registered font definitions.
     */
    public function all(): array
    {
        return $this->fonts;
    }

    /**
     * Unregister a font family.
     */
    public function remove(string $family): void
    {
        unset($this->fonts[$family]);
    }

    /**
     * Get a registered preset instance by name.
     */
    public function getPreset(string $name): ?PresetInterface
    {
        return $this->presets[strtolower($name)] ?? null;
    }

    /**
     * Register a custom preset.
     */
    public function registerPreset(PresetInterface $preset): void
    {
        $this->presets[strtolower($preset->getName())] = $preset;
    }

    /**
     * Get the font resolver.
     */
    public function getResolver(): FontResolver
    {
        return $this->resolver;
    }

    /**
     * Get the font detector.
     */
    public function getDetector(): FontDetector
    {
        return $this->detector;
    }

    /**
     * Auto-discover TTF and OTF fonts from a directory.
     *
     * @return array<string, array<string, mixed>>
     */
    public function discoverDirectory(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $discovered = [];
        $files = glob(rtrim($directory, '/\\').'/*.{ttf,otf,TTF,OTF}', GLOB_BRACE);

        if ($files) {
            foreach ($files as $file) {
                try {
                    $meta = FontMetadata::parse($file);
                    $family = $meta['family'];
                    $style = strtolower($meta['subfamily']);

                    $styleKey = match ($style) {
                        'bold' => 'bold',
                        'italic', 'oblique' => 'italic',
                        'bold italic', 'bolditalic' => 'bold_italic',
                        default => 'regular',
                    };

                    if (! isset($discovered[$family])) {
                        $discovered[$family] = [
                            'family' => $family,
                            'scripts' => $meta['supported_scripts'],
                        ];
                    }

                    $discovered[$family][$styleKey] = $file;
                    $this->register($discovered[$family]);
                } catch (\Throwable) {
                    // Skip unparseable files
                }
            }
        }

        return $discovered;
    }
}
