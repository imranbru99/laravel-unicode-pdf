<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Native;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

class HtmlLayouter
{
    /**
     * @var array<string, array<string, string>>
     */
    protected array $stylesheet = [];

    /**
     * @var list<array<string, mixed>>
     */
    protected array $commands = [];

    /**
     * @var list<array<string, mixed>>
     */
    protected array $lineBuffer = [];

    protected float $lineWidth = 0;

    protected float $x = 0;

    protected float $y = 0;

    protected float $contentWidth = 0;

    protected float $left = 0;

    protected float $right = 0;

    protected float $pageHeight = 0;

    protected float $top = 0;

    protected float $bottom = 0;

    /**
     * @var list<string>
     */
    protected array $fontFamilies = [];

    /**
     * @var array{0: float, 1: float, 2: float}
     */
    protected array $color = [0.1, 0.1, 0.1];

    protected float $fontSize = 11;

    protected string $align = 'left';

    protected string $direction = 'ltr';

    protected string $fontStyle = 'regular';

    protected float $lineHeight = 1.35;

    protected bool $underline = false;

    /**
     * @var array{0: float, 1: float, 2: float}|null
     */
    protected ?array $background = null;

    protected float $marginTop = 0;

    protected float $marginBottom = 0;

    protected float $padding = 0;

    /**
     * @param  list<string>  $fontFamilies
     * @return list<array<string, mixed>>
     */
    public function layout(
        string $html,
        FontLibrary $fonts,
        float $pageWidth,
        float $pageHeight,
        float $marginLeft,
        float $marginTop,
        float $marginRight,
        float $marginBottom,
        array $fontFamilies = [],
        string $direction = 'ltr'
    ): array {
        $this->fontFamilies = $fontFamilies;
        $this->direction = $direction === 'rtl' ? 'rtl' : 'ltr';
        $this->align = $this->direction === 'rtl' ? 'right' : 'left';
        $this->pageHeight = $pageHeight;
        $this->top = $marginTop;
        $this->bottom = $marginBottom;
        $this->left = $marginLeft;
        $this->right = $pageWidth - $marginRight;
        $this->contentWidth = $this->right - $this->left;
        $this->x = $this->left;
        $this->y = $this->top;
        $this->commands = [];
        $this->stylesheet = [];
        $this->lineBuffer = [];
        $this->lineWidth = 0;

        $dom = $this->loadDom($html);
        $this->collectStyles($dom);
        $body = $dom->getElementsByTagName('body')->item(0) ?? $dom->documentElement;
        if ($body instanceof DOMElement) {
            $this->walk($body, $fonts);
        }
        $this->flushLine();

        return $this->commands;
    }

    protected function loadDom(string $html): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        // libxml's HTML parser defaults to ISO-8859-1. Numeric entities keep
        // Bengali / Devanagari / Arabic codepoints intact.
        $encoded = mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, 0x10FFFF], 'UTF-8');
        if (! preg_match('/<html[\s>]/i', $html)) {
            $encoded = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>'.$encoded.'</body></html>';
        }

        $dom->loadHTML($encoded, LIBXML_HTML_NODEFDTD | LIBXML_NOERROR);
        $dom->encoding = 'UTF-8';
        libxml_use_internal_errors($previous);

        return $dom;
    }

    protected function collectStyles(DOMDocument $dom): void
    {
        foreach ($dom->getElementsByTagName('style') as $style) {
            $css = $style->textContent ?? '';
            if (preg_match_all('/([^{]+)\{([^}]+)\}/u', $css, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $selectors = array_map('trim', explode(',', $match[1]));
                    $rules = $this->parseDeclarations($match[2]);
                    foreach ($selectors as $selector) {
                        $selector = strtolower(preg_replace('/\s+/', ' ', $selector) ?? $selector);
                        $this->stylesheet[$selector] = array_merge($this->stylesheet[$selector] ?? [], $rules);
                    }
                }
            }
        }
    }

    /**
     * @return array<string, string>
     */
    protected function parseDeclarations(string $css): array
    {
        $rules = [];
        foreach (explode(';', $css) as $declaration) {
            if (! str_contains($declaration, ':')) {
                continue;
            }
            [$property, $value] = array_map('trim', explode(':', $declaration, 2));
            if ($property !== '') {
                $rules[strtolower($property)] = $value;
            }
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    protected function computedStyle(DOMElement $element): array
    {
        $style = [];
        $tag = strtolower($element->tagName);
        $style = array_merge($style, $this->stylesheet[$tag] ?? []);

        $class = trim((string) $element->getAttribute('class'));
        $classes = $class !== '' ? (preg_split('/\s+/', $class) ?: []) : [];
        foreach ($classes as $name) {
            $style = array_merge($style, $this->stylesheet[$tag.'.'.$name] ?? []);
            $style = array_merge($style, $this->stylesheet['.'.$name] ?? []);
        }

        $id = trim((string) $element->getAttribute('id'));
        if ($id !== '') {
            $style = array_merge($style, $this->stylesheet[$tag.'#'.$id] ?? []);
            $style = array_merge($style, $this->stylesheet['#'.$id] ?? []);
        }

        $inline = trim((string) $element->getAttribute('style'));
        if ($inline !== '') {
            $style = array_merge($style, $this->parseDeclarations($inline));
        }

        $htmlAlign = strtolower((string) $element->getAttribute('align'));
        if (in_array($htmlAlign, ['left', 'right', 'center', 'justify'], true) && ! isset($style['text-align'])) {
            $style['text-align'] = $htmlAlign;
        }

        return $style;
    }

    protected function walk(DOMNode $node, FontLibrary $fonts): void
    {
        if ($node instanceof DOMText) {
            $text = $this->normalizeText($node->textContent ?? '');
            if ($text !== '') {
                $this->writeText($text, $fonts);
            }

            return;
        }

        if (! $node instanceof DOMElement) {
            return;
        }

        $tag = strtolower($node->tagName);
        if (in_array($tag, ['style', 'script', 'head', 'title', 'meta', 'link'], true)) {
            return;
        }

        $previous = $this->pushStyle($this->computedStyle($node), $tag);

        if ($tag === 'br') {
            $this->newLine();
            $this->popStyle($previous);

            return;
        }

        if ($tag === 'hr') {
            $this->flushLine();
            $this->newLine();
            $this->commands[] = [
                'type' => 'line',
                'x1' => $this->left,
                'y1' => $this->y,
                'x2' => $this->right,
                'y2' => $this->y,
                'color' => [0.7, 0.7, 0.7],
                'width' => 0.6,
            ];
            $this->y += 10;
            $this->popStyle($previous);

            return;
        }

        if ($tag === 'img') {
            $this->flushLine();
            $this->drawImage((string) $node->getAttribute('src'), $node);
            $this->popStyle($previous);

            return;
        }

        if ($tag === 'table') {
            $this->flushLine();
            $this->drawTable($node, $fonts);
            $this->popStyle($previous);

            return;
        }

        $isBlock = in_array($tag, ['p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li', 'section', 'article', 'header', 'footer', 'ul', 'ol', 'blockquote'], true);
        $blockStart = null;
        $style = $this->computedStyle($node);

        if ($isBlock && $this->wantsPageBreakBefore($style)) {
            $this->forcePageBreak();
        }

        if ($isBlock) {
            $this->flushLine();
            if ($this->x > $this->left + 0.5) {
                $this->newLine();
            }
            $this->y += $this->marginTop;
            $blockStart = [
                'y' => $this->y,
                'index' => count($this->commands),
                'background' => $this->background,
            ];
            $this->y += $this->padding;
        }

        if ($tag === 'li') {
            $this->writeText('• ', $fonts);
        }

        foreach (iterator_to_array($node->childNodes) as $child) {
            $this->walk($child, $fonts);
        }

        if ($isBlock) {
            $this->flushLine();
            $this->newLine();
            $this->y += $this->padding;
            $blockBackground = $blockStart['background'];
            if ($blockBackground !== null) {
                $height = max($this->fontSize, $this->y - (float) $blockStart['y']);
                array_splice($this->commands, (int) $blockStart['index'], 0, [[
                    'type' => 'rect',
                    'x' => $this->left,
                    'y' => $blockStart['y'],
                    'w' => $this->contentWidth,
                    'h' => $height,
                    'fill' => $blockBackground,
                ]]);
            }
            $this->y += $this->marginBottom;
        }

        $this->popStyle($previous);
    }

    /**
     * @param  array<string, string>  $style
     * @return array<string, mixed>
     */
    protected function pushStyle(array $style, string $tag): array
    {
        $previous = [
            'color' => $this->color,
            'fontSize' => $this->fontSize,
            'align' => $this->align,
            'direction' => $this->direction,
            'fontStyle' => $this->fontStyle,
            'fontFamilies' => $this->fontFamilies,
            'underline' => $this->underline,
            'background' => $this->background,
            'lineHeight' => $this->lineHeight,
            'marginTop' => $this->marginTop,
            'marginBottom' => $this->marginBottom,
            'padding' => $this->padding,
        ];

        $this->fontSize = match ($tag) {
            'h1' => 22.0,
            'h2' => 18.0,
            'h3' => 15.0,
            'h4' => 13.0,
            'h5' => 12.0,
            'h6' => 11.0,
            'small' => max(8.0, $this->fontSize * 0.85),
            default => $this->fontSize,
        };

        if (isset($style['font-size'])) {
            $this->fontSize = $this->parseSize($style['font-size']);
        }
        if (isset($style['color'])) {
            $this->color = $this->parseColor($style['color']);
        }
        if (isset($style['text-align'])) {
            $this->align = strtolower($style['text-align']);
        }
        if (isset($style['direction'])) {
            $this->direction = strtolower($style['direction']) === 'rtl' ? 'rtl' : 'ltr';
        }
        if (isset($style['font-weight'])) {
            $weight = strtolower($style['font-weight']);
            $this->fontStyle = ((int) $style['font-weight'] >= 600 || $weight === 'bold' || $weight === 'bolder')
                ? 'bold'
                : 'regular';
        }
        if (isset($style['font-style']) && in_array(strtolower($style['font-style']), ['italic', 'oblique'], true)) {
            $this->fontStyle = $this->fontStyle === 'bold' ? 'bold_italic' : 'italic';
        }
        if (in_array($tag, ['b', 'strong'], true)) {
            $this->fontStyle = $this->fontStyle === 'italic' ? 'bold_italic' : 'bold';
        }
        if (in_array($tag, ['i', 'em'], true)) {
            $this->fontStyle = $this->fontStyle === 'bold' ? 'bold_italic' : 'italic';
        }
        if (isset($style['font-family'])) {
            $this->fontFamilies = array_values(array_unique(array_merge(
                $this->parseFontFamily($style['font-family']),
                $this->fontFamilies
            )));
        }
        if (isset($style['line-height'])) {
            $this->lineHeight = $this->parseLineHeight($style['line-height']);
        }
        $decoration = strtolower($style['text-decoration'] ?? '');
        $this->underline = str_contains($decoration, 'underline') || $tag === 'u';
        if (isset($style['background-color']) || isset($style['background'])) {
            $this->background = $this->parseColor($style['background-color'] ?? $style['background'] ?? '');
        }
        if (isset($style['margin-top'])) {
            $this->marginTop = $this->parseSize($style['margin-top']);
        } elseif (isset($style['margin'])) {
            $this->marginTop = $this->parseSize($style['margin']);
        }
        if (isset($style['margin-bottom'])) {
            $this->marginBottom = $this->parseSize($style['margin-bottom']);
        } elseif (isset($style['margin'])) {
            $this->marginBottom = $this->parseSize($style['margin']);
        }
        if (isset($style['padding'])) {
            $this->padding = $this->parseSize($style['padding']);
        } elseif (isset($style['padding-top'])) {
            $this->padding = $this->parseSize($style['padding-top']);
        }

        return $previous;
    }

    /**
     * @param  array<string, mixed>  $previous
     */
    protected function popStyle(array $previous): void
    {
        $this->color = $previous['color'];
        $this->fontSize = $previous['fontSize'];
        $this->align = $previous['align'];
        $this->direction = $previous['direction'];
        $this->fontStyle = $previous['fontStyle'];
        $this->fontFamilies = $previous['fontFamilies'];
        $this->underline = $previous['underline'];
        $this->background = $previous['background'];
        $this->lineHeight = $previous['lineHeight'];
        $this->marginTop = $previous['marginTop'];
        $this->marginBottom = $previous['marginBottom'];
        $this->padding = $previous['padding'];
    }

    protected function writeText(string $text, FontLibrary $fonts): void
    {
        $tokens = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        foreach ($tokens as $token) {
            if ($token === '') {
                continue;
            }
            $run = $this->measureRun($token, $fonts);
            if ($this->lineWidth + $run['width'] > $this->contentWidth && $this->lineWidth > 0.5 && ! preg_match('/^\s+$/u', $token)) {
                $this->newLine();
            }

            foreach ($run['parts'] as $part) {
                $part['size'] = $this->fontSize;
                $part['color'] = $this->color;
                $part['underline'] = $this->underline;
                $this->lineBuffer[] = $part;
                $this->lineWidth += (float) $part['width'];
            }
        }
    }

    protected function flushLine(): void
    {
        if ($this->lineBuffer === []) {
            return;
        }

        $lineHasRtl = false;
        foreach ($this->lineBuffer as $part) {
            if (Shaper::isRtlText((string) ($part['text'] ?? ''))) {
                $lineHasRtl = true;
                break;
            }
        }
        if ($lineHasRtl) {
            $this->lineBuffer = array_reverse($this->lineBuffer);
        }

        $offset = match ($this->align) {
            'center' => ($this->contentWidth - $this->lineWidth) / 2,
            'right' => $this->contentWidth - $this->lineWidth,
            default => 0.0,
        };
        $x = $this->left + max(0.0, $offset);

        foreach ($this->lineBuffer as $part) {
            $this->commands[] = [
                'type' => 'text',
                'x' => $x,
                'y' => $this->y,
                'size' => $part['size'],
                'color' => $part['color'],
                'font' => $part['font'],
                'gids' => $part['gids'],
                'positions' => $part['positions'] ?? [],
                'codepoints' => $part['codepoints'],
                'core' => $part['core'],
                'text' => $part['text'],
                'underline' => $part['underline'] ?? false,
            ];
            if (! empty($part['underline'])) {
                $underlineY = $this->y + (float) $part['size'] + 1;
                $this->commands[] = [
                    'type' => 'line',
                    'x1' => $x,
                    'y1' => $underlineY,
                    'x2' => $x + (float) $part['width'],
                    'y2' => $underlineY,
                    'color' => $part['color'],
                    'width' => 0.6,
                ];
            }
            $x += (float) $part['width'];
        }

        $this->lineBuffer = [];
        $this->lineWidth = 0;
        $this->x = $this->left;
    }

    /**
     * @return array{width: float, parts: list<array<string, mixed>>}
     */
    protected function measureRun(string $text, FontLibrary $fonts): array
    {
        $parts = [];
        $width = 0.0;
        $buffer = '';
        $currentKey = null;

        $currentBidi = null;
        foreach (Shaper::codepoints($text) as $cp) {
            $font = $fonts->fontForCodepoint($cp, $this->fontFamilies, $this->fontStyle);
            $key = $font instanceof TtfFont ? $font->path : 'core';
            $kind = $this->bidiKind($cp);
            $bidiKey = $kind === 'N' ? $currentBidi : $kind;
            if ($currentKey !== null && $buffer !== '' && ($key !== $currentKey || ($currentBidi !== null && $bidiKey !== null && $bidiKey !== $currentBidi))) {
                $part = $this->shapePart($buffer, $currentKey, $fonts, Shaper::isRtlText($buffer));
                $parts[] = $part;
                $width += $part['width'];
                $buffer = '';
            }
            $currentKey = $key;
            if ($kind !== 'N') {
                $currentBidi = $kind;
            }
            $buffer .= mb_chr($cp, 'UTF-8');
        }

        if ($buffer !== '') {
            $part = $this->shapePart($buffer, $currentKey, $fonts, Shaper::isRtlText($buffer));
            $parts[] = $part;
            $width += $part['width'];
        }

        return ['width' => $width, 'parts' => $parts];
    }

    protected function bidiKind(int $codepoint): string
    {
        if (
            ($codepoint >= 0x0590 && $codepoint <= 0x08FF)
            || ($codepoint >= 0xFB1D && $codepoint <= 0xFEFF)
        ) {
            return 'R';
        }

        if (
            ($codepoint >= 0x0030 && $codepoint <= 0x0039)
            || ($codepoint >= 0x0041 && $codepoint <= 0x005A)
            || ($codepoint >= 0x0061 && $codepoint <= 0x007A)
        ) {
            return 'L';
        }

        return 'N';
    }

    /**
     * @return array<string, mixed>
     */
    protected function shapePart(string $text, ?string $fontKey, FontLibrary $fonts, bool $rtl): array
    {
        if ($fontKey && $fontKey !== 'core') {
            $font = TtfFont::load($fontKey);
            $shaped = Shaper::shape($text, $font, $rtl);
            $positions = $shaped['positions'];
            $width = 0.0;
            if ($positions !== []) {
                foreach ($positions as $pos) {
                    $width = max($width, ((float) $pos['x'] + (float) $pos['w']) * $this->fontSize);
                }
            } else {
                foreach ($shaped['gids'] as $gid) {
                    $width += $font->advance($gid, $this->fontSize);
                }
            }

            return [
                'font' => $font,
                'gids' => $shaped['gids'],
                'positions' => $positions,
                'codepoints' => $shaped['codepoints'],
                'core' => false,
                'text' => $text,
                'width' => $width,
            ];
        }

        $width = 0.0;
        $safe = '';
        $codepoints = [];
        foreach (Shaper::codepoints($text) as $cp) {
            if (! CoreFont::has($cp)) {
                continue;
            }
            $safe .= mb_chr($cp, 'UTF-8');
            $codepoints[] = $cp;
            $width += CoreFont::width($cp, $this->fontSize);
        }

        return [
            'font' => null,
            'gids' => $codepoints,
            'codepoints' => $codepoints,
            'core' => true,
            'text' => $safe,
            'width' => $width,
        ];
    }

    protected function newLine(): void
    {
        $this->flushLine();
        $this->x = $this->left;
        $this->y += $this->fontSize * $this->lineHeight;
        if ($this->y > $this->pageHeight - $this->bottom) {
            $this->forcePageBreak();
        }
    }

    /**
     * @param  array<string, string>  $style
     */
    protected function wantsPageBreakBefore(array $style): bool
    {
        $value = strtolower($style['page-break-before'] ?? $style['break-before'] ?? '');

        return in_array($value, ['always', 'page', 'left', 'right'], true);
    }

    protected function forcePageBreak(): void
    {
        $this->flushLine();
        $last = $this->commands !== [] ? $this->commands[array_key_last($this->commands)] : null;
        if ($this->y <= $this->top + 0.5 && (($last['type'] ?? '') === 'pagebreak' || $this->commands === [])) {
            $this->x = $this->left;

            return;
        }

        $this->commands[] = ['type' => 'pagebreak'];
        $this->y = $this->top;
        $this->x = $this->left;
    }

    protected function drawTable(DOMElement $table, FontLibrary $fonts): void
    {
        $rows = [];
        foreach ($table->getElementsByTagName('tr') as $tr) {
            $cells = [];
            foreach ($tr->childNodes as $cell) {
                if ($cell instanceof DOMElement && in_array(strtolower($cell->tagName), ['td', 'th'], true)) {
                    $cellStyle = $this->computedStyle($cell);
                    $cells[] = [
                        'text' => trim(preg_replace('/\s+/u', ' ', $cell->textContent ?? '') ?? ''),
                        'header' => strtolower($cell->tagName) === 'th',
                        'align' => $cellStyle['text-align'] ?? (str_contains((string) $cell->getAttribute('class'), 'text-right') ? 'right' : 'left'),
                        'colspan' => max(1, (int) ($cell->getAttribute('colspan') ?: 1)),
                        'color' => isset($cellStyle['color']) ? $this->parseColor($cellStyle['color']) : null,
                        'background' => isset($cellStyle['background-color']) ? $this->parseColor($cellStyle['background-color']) : null,
                        'fontSize' => isset($cellStyle['font-size']) ? $this->parseSize($cellStyle['font-size']) : null,
                    ];
                }
            }
            if ($cells !== []) {
                $rows[] = $cells;
            }
        }

        if ($rows === []) {
            return;
        }

        $columns = 0;
        foreach ($rows[0] as $cell) {
            $columns += $cell['colspan'];
        }
        $columns = max(1, $columns);
        $colWidth = $this->contentWidth / $columns;
        $this->newLine();

        foreach ($rows as $row) {
            $col = 0;
            $rowHeight = $this->fontSize * $this->lineHeight + 22;
            $cellTop = $this->y;

            if ($cellTop + $rowHeight > $this->pageHeight - $this->bottom) {
                $this->commands[] = ['type' => 'pagebreak'];
                $this->y = $this->top;
                $cellTop = $this->y;
            }

            foreach ($row as $cell) {
                $span = $cell['colspan'];
                $x = $this->left + ($col * $colWidth);
                $w = $colWidth * $span;
                $bg = $cell['background'] ?? ($cell['header'] ? [0.92, 0.96, 1.0] : [1.0, 1.0, 1.0]);

                $this->commands[] = [
                    'type' => 'rect',
                    'x' => $x,
                    'y' => $cellTop,
                    'w' => $w,
                    'h' => $rowHeight,
                    'fill' => $bg,
                    'stroke' => [0.8, 0.84, 0.88],
                ];

                $previous = [
                    'align' => $this->align,
                    'fontStyle' => $this->fontStyle,
                    'color' => $this->color,
                    'fontSize' => $this->fontSize,
                ];
                $this->align = $cell['align'];
                if ($cell['header']) {
                    $this->fontStyle = 'bold';
                }
                if (is_array($cell['color'])) {
                    $this->color = $cell['color'];
                }
                if (is_float($cell['fontSize'])) {
                    $this->fontSize = $cell['fontSize'];
                }
                $prevLeft = $this->left;
                $prevRight = $this->right;
                $prevWidth = $this->contentWidth;
                $this->left = $x + 4;
                $this->right = $x + $w - 4;
                $this->contentWidth = max(1.0, $w - 8);
                $this->x = $this->left;
                $this->y = $cellTop + 10;
                $this->writeText($cell['text'], $fonts);
                $this->flushLine();
                $this->left = $prevLeft;
                $this->right = $prevRight;
                $this->contentWidth = $prevWidth;
                $this->align = $previous['align'];
                $this->fontStyle = $previous['fontStyle'];
                $this->color = $previous['color'];
                $this->fontSize = $previous['fontSize'];
                $col += $span;
            }

            $this->y = $cellTop + $rowHeight;
            $this->x = $this->left;
        }

        $this->y += 6;
    }

    protected function drawImage(string $src, DOMElement $node): void
    {
        if ($src === '') {
            return;
        }

        $data = null;
        if (str_starts_with($src, 'data:image/')) {
            $parts = explode(',', $src, 2);
            $data = isset($parts[1]) ? base64_decode($parts[1], true) : false;
        } elseif (is_readable($src)) {
            $data = file_get_contents($src);
        }

        if (! is_string($data) || $data === '') {
            return;
        }

        $width = (float) ($node->getAttribute('width') ?: 120);
        $height = (float) ($node->getAttribute('height') ?: 80);
        $this->commands[] = [
            'type' => 'image',
            'x' => $this->x,
            'y' => $this->y,
            'w' => $width,
            'h' => $height,
            'data' => $data,
        ];
        $this->y += $height + 6;
    }

    protected function normalizeText(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\R+/u', ' ', $text) ?? $text;

        return $text;
    }

    /**
     * @return list<string>
     */
    protected function parseFontFamily(string $value): array
    {
        $families = [];
        if (preg_match_all('/"([^"]+)"|\'([^\']+)\'|([^,]+)/', $value, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $name = trim($match[1] ?: $match[2] ?: $match[3], " \t\"'");
                $generic = ['serif', 'sans-serif', 'monospace', 'cursive', 'fantasy', 'system-ui'];
                if ($name !== '' && ! in_array(strtolower($name), $generic, true)) {
                    $families[] = $name;
                }
            }
        }

        return $families;
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    protected function parseColor(string $value): array
    {
        $value = trim($value);
        $named = [
            'black' => [0.0, 0.0, 0.0],
            'white' => [1.0, 1.0, 1.0],
            'red' => [0.8, 0.1, 0.1],
            'green' => [0.13, 0.55, 0.13],
            'blue' => [0.17, 0.42, 0.69],
            'navy' => [0.0, 0.0, 0.5],
            'teal' => [0.0, 0.5, 0.5],
            'orange' => [1.0, 0.55, 0.0],
            'purple' => [0.5, 0.0, 0.5],
            'gray' => [0.45, 0.45, 0.45],
            'grey' => [0.45, 0.45, 0.45],
            'silver' => [0.75, 0.75, 0.75],
            'maroon' => [0.5, 0.0, 0.0],
        ];
        if (isset($named[strtolower($value)])) {
            return $named[strtolower($value)];
        }
        if (preg_match('/^#([0-9a-f]{3})$/i', $value, $m)) {
            return [
                hexdec($m[1][0].$m[1][0]) / 255,
                hexdec($m[1][1].$m[1][1]) / 255,
                hexdec($m[1][2].$m[1][2]) / 255,
            ];
        }
        if (preg_match('/^#([0-9a-f]{6})$/i', $value, $m)) {
            return [
                hexdec(substr($m[1], 0, 2)) / 255,
                hexdec(substr($m[1], 2, 2)) / 255,
                hexdec(substr($m[1], 4, 2)) / 255,
            ];
        }
        if (preg_match('/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/', $value, $m)) {
            return [(int) $m[1] / 255, (int) $m[2] / 255, (int) $m[3] / 255];
        }

        return $this->color;
    }

    protected function parseSize(string $value): float
    {
        $value = trim($value);
        if (preg_match('/([\d.]+)\s*px/i', $value, $m)) {
            return (float) $m[1] * 0.75;
        }
        if (preg_match('/([\d.]+)\s*pt/i', $value, $m)) {
            return (float) $m[1];
        }
        if (preg_match('/([\d.]+)\s*em/i', $value, $m)) {
            return (float) $m[1] * $this->fontSize;
        }
        if (preg_match('/([\d.]+)\s*rem/i', $value, $m)) {
            return (float) $m[1] * 11;
        }
        if (preg_match('/([\d.]+)\s*%/i', $value, $m)) {
            return $this->fontSize * ((float) $m[1] / 100);
        }
        if (preg_match('/([\d.]+)\s*mm/i', $value, $m)) {
            return (float) $m[1] * 72 / 25.4;
        }

        return is_numeric($value) ? (float) $value : $this->fontSize;
    }

    protected function parseLineHeight(string $value): float
    {
        $value = trim($value);
        if ($value === 'normal') {
            return 1.35;
        }
        if (is_numeric($value)) {
            return max(0.8, (float) $value);
        }
        if (preg_match('/([\d.]+)\s*%/i', $value, $m)) {
            return max(0.8, (float) $m[1] / 100);
        }

        $absolute = $this->parseSize($value);

        return $this->fontSize > 0 ? max(0.8, $absolute / $this->fontSize) : 1.35;
    }
}
