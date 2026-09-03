<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Services;

use ImranDev\UnicodePdf\Fonts\FontCssHelper;
use ImranDev\UnicodePdf\Fonts\FontFallback;
use ImranDev\UnicodePdf\Fonts\FontManager;
use ImranDev\UnicodePdf\Unicode\BidiProcessor;
use ImranDev\UnicodePdf\Unicode\DirectionDetector;
use ImranDev\UnicodePdf\Unicode\ScriptDetector;
use ImranDev\UnicodePdf\Unicode\UnicodeNormalizer;
use ImranDev\UnicodePdf\Unicode\Utf8Validator;

class PdfRenderer
{
    public function __construct(
        protected Utf8Validator $utf8Validator = new Utf8Validator,
        protected UnicodeNormalizer $normalizer = new UnicodeNormalizer,
        protected ScriptDetector $scriptDetector = new ScriptDetector,
        protected DirectionDetector $directionDetector = new DirectionDetector,
        protected BidiProcessor $bidiProcessor = new BidiProcessor,
        protected ?FontManager $fontManager = null
    ) {}

    /**
     * Prepare, normalize, and inject font styling into HTML content.
     *
     * @param  array<string>  $fallbackFonts
     * @param  string  $direction  ('auto' | 'ltr' | 'rtl')
     * @return array{
     *     html: string,
     *     detected_direction: string,
     *     detected_scripts: array<string, int>,
     *     dominant_script: string,
     *     resolved_fonts: array<string>
     * }
     */
    public function prepareHtml(
        string $html,
        string $primaryFont = 'Noto Sans',
        array $fallbackFonts = [],
        string $direction = 'auto',
        bool $autoInjectCss = true,
        bool $normalize = false,
        string $normalizeForm = 'NFC',
        string $htmlLang = 'en',
        string $extraCss = ''
    ): array {
        // 1. Validate UTF-8
        $this->utf8Validator->validate($html);

        // 2. Normalization if requested
        if ($normalize) {
            $html = $this->normalizer->normalize($html, $normalizeForm);
        }

        // 3. Script & Direction Detection
        $detectedScripts = $this->scriptDetector->detect($html);
        $dominantScript = $this->scriptDetector->getDominantScript($html);

        $detectedDirection = $direction === 'auto'
            ? $this->directionDetector->detect($html)
            : $direction;

        // 4. Resolve Fonts & Fallbacks
        $resolvedFonts = $this->fontManager
            ? $this->fontManager->getResolver()->resolve($html, $primaryFont)
            : [$primaryFont];

        $fallbackBuilder = new FontFallback($fallbackFonts);
        $fontChain = $fallbackBuilder->getChain($primaryFont, $resolvedFonts);
        $fontFamilyStack = $fallbackBuilder->toCss($primaryFont, $resolvedFonts);

        // 5. Ensure UTF-8 meta and HTML dir attribute
        $processedHtml = $this->ensureHtmlStructure($html, $detectedDirection, $htmlLang);

        // 6. Inject CSS @font-face and font-family rules
        if ($autoInjectCss && $this->fontManager) {
            $registeredFonts = $this->fontManager->all();
            $fontFaceCss = FontCssHelper::generateFontFaceCss($registeredFonts);
            if ($extraCss !== '') {
                $fontFaceCss .= "\n".$extraCss;
            }
            $processedHtml = FontCssHelper::injectIntoHtml($processedHtml, $fontFaceCss, $fontFamilyStack);
        } elseif ($extraCss !== '') {
            $processedHtml = FontCssHelper::injectIntoHtml($processedHtml, $extraCss, $primaryFont);
        }

        return [
            'html' => $processedHtml,
            'detected_direction' => $detectedDirection,
            'detected_scripts' => $detectedScripts,
            'dominant_script' => $dominantScript,
            'resolved_fonts' => $fontChain,
        ];
    }

    /**
     * Ensure standard HTML wrapper with meta charset UTF-8 and dir attribute.
     */
    protected function ensureHtmlStructure(string $html, string $direction, string $lang = 'en'): string
    {
        $hasHtmlTag = (bool) preg_match('/<html[^>]*>/i', $html);
        $hasHeadTag = (bool) preg_match('/<head[^>]*>/i', $html);
        $lang = htmlspecialchars($lang, ENT_QUOTES, 'UTF-8');
        $direction = htmlspecialchars($direction, ENT_QUOTES, 'UTF-8');

        if (! $hasHtmlTag) {
            $html = "<!DOCTYPE html>\n<html lang=\"{$lang}\" dir=\"{$direction}\">\n<head>\n<meta charset=\"UTF-8\">\n</head>\n<body>\n{$html}\n</body>\n</html>";
        } else {
            // If dir is not set on <html>, add it
            if (! preg_match('/<html[^>]*\sdir=/i', $html)) {
                $html = preg_replace('/<html/i', "<html dir=\"{$direction}\"", $html, 1) ?? $html;
            }

            // Ensure meta charset=UTF-8 in <head>
            if (! preg_match('/<meta[^>]*charset=/i', $html)) {
                if ($hasHeadTag) {
                    $html = preg_replace('/<head[^>]*>/i', "$0\n<meta charset=\"UTF-8\">", $html, 1) ?? $html;
                }
            }
        }

        return $html;
    }
}
