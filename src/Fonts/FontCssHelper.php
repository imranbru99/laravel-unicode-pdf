<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Fonts;

class FontCssHelper
{
    /**
     * Generate @font-face CSS blocks for registered fonts.
     *
     * @param  array<string, array<string, mixed>>  $fonts
     */
    public static function generateFontFaceCss(array $fonts, bool $embedAsBase64 = false): string
    {
        $css = [];

        foreach ($fonts as $family => $variants) {
            $styles = [
                'regular' => ['weight' => 'normal', 'style' => 'normal'],
                'bold' => ['weight' => 'bold', 'style' => 'normal'],
                'italic' => ['weight' => 'normal', 'style' => 'italic'],
                'bold_italic' => ['weight' => 'bold', 'style' => 'italic'],
            ];

            foreach ($styles as $key => $props) {
                $path = $variants[$key] ?? null;
                if (! $path || ! file_exists($path)) {
                    continue;
                }

                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $format = match ($ext) {
                    'otf' => 'opentype',
                    'woff' => 'woff',
                    'woff2' => 'woff2',
                    default => 'truetype',
                };

                $src = $embedAsBase64
                    ? 'url(data:font/'.$format.';charset=utf-8;base64,'.base64_encode(file_get_contents($path)).')'
                    : "url('".str_replace('\\', '/', $path)."')";

                $css[] = "@font-face {\n".
                    "    font-family: '".addslashes($family)."';\n".
                    "    font-weight: {$props['weight']};\n".
                    "    font-style: {$props['style']};\n".
                    "    src: {$src} format('{$format}');\n".
                    '}';
            }
        }

        return implode("\n\n", $css);
    }

    /**
     * Inject generated font-face CSS and body styling into HTML string.
     */
    public static function injectIntoHtml(string $html, string $cssRules, string $fontFamilyStack): string
    {
        $styleBlock = "<style>\n".
            $cssRules."\n\n".
            "body, p, div, span, table, th, td, h1, h2, h3, h4, h5, h6 {\n".
            "    font-family: {$fontFamilyStack};\n".
            "}\n".
            '</style>';

        if (stripos($html, '</head>') !== false) {
            return preg_replace('/<\/head>/i', $styleBlock."\n</head>", $html, 1) ?? $html;
        }

        if (stripos($html, '<html') !== false) {
            return preg_replace('/(<html[^>]*>)/i', "$1\n<head>\n".$styleBlock."\n</head>", $html, 1) ?? $html;
        }

        return "<head>\n".$styleBlock."\n</head>\n".$html;
    }
}
