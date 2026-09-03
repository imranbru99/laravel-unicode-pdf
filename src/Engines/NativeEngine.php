<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Engines;

use ImranDev\UnicodePdf\Enums\PaperSize;
use ImranDev\UnicodePdf\Native\FontDownloader;
use ImranDev\UnicodePdf\Native\FontLibrary;
use ImranDev\UnicodePdf\Native\HtmlLayouter;
use ImranDev\UnicodePdf\Native\PdfWriter;
use ImranDev\UnicodePdf\Native\TtfFont;

class NativeEngine extends AbstractPdfEngine
{
    public function getName(): string
    {
        return 'native';
    }

    public function supports(string $capability): bool
    {
        return match (strtolower($capability)) {
            'unicode' => true,
            'rtl' => true,
            'font-shaping' => true,
            'svg' => false,
            'javascript' => false,
            'encryption' => false,
            'attachments' => false,
            default => false,
        };
    }

    public function output(): string
    {
        FontDownloader::ensure(FontLibrary::packageFontPath());

        $prepared = $this->getPreparedContent();
        [$pageWidth, $pageHeight] = $this->pageDimensions();
        $margins = $this->marginsInPoints();

        $library = new FontLibrary(
            $this->fontManager,
            $this->fontSearchDirectories()
        );

        $families = array_values(array_filter(array_merge(
            [$this->primaryFont],
            $this->fallbackFonts,
            $prepared['resolved_fonts']
        )));

        $layouter = new HtmlLayouter;
        $commands = $layouter->layout(
            html: $prepared['html'],
            fonts: $library,
            pageWidth: $pageWidth,
            pageHeight: $pageHeight,
            marginLeft: $margins['left'],
            marginTop: $margins['top'] + ($this->headerHtml ? 18 : 0),
            marginRight: $margins['right'],
            marginBottom: $margins['bottom'] + ($this->footerHtml || $this->pageNumberFormat ? 18 : 0),
            fontFamilies: $families,
            direction: $prepared['detected_direction']
        );

        return $this->buildPdf($commands, $pageWidth, $pageHeight, $margins, $prepared['html']);
    }

    /**
     * @param  list<array<string, mixed>>  $commands
     * @param  array{top: float, right: float, bottom: float, left: float}  $margins
     */
    protected function buildPdf(array $commands, float $pageWidth, float $pageHeight, array $margins, string $sourceHtml): string
    {
        $pages = [[]];
        foreach ($commands as $command) {
            if (($command['type'] ?? '') === 'pagebreak') {
                $pages[] = [];

                continue;
            }
            $pages[array_key_last($pages)][] = $command;
        }

        $writer = new PdfWriter;
        $usedFonts = [];
        foreach ($pages as $pageCommands) {
            foreach ($pageCommands as $command) {
                if (($command['type'] ?? '') === 'text' && ($command['font'] ?? null) instanceof TtfFont) {
                    $usedFonts[$command['font']->path] = $command['font'];
                }
            }
        }

        $fontRefs = [];
        $fontResource = '';
        $coreFontId = $writer->add('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>');
        $fontResource .= '/F0 '.$coreFontId.' 0 R ';

        $index = 1;
        foreach ($usedFonts as $path => $font) {
            $fontRefs[$path] = 'F'.$index;
            $type0Id = $this->embedFont($writer, $font);
            $fontResource .= '/F'.$index.' '.$type0Id.' 0 R ';
            $index++;
        }

        $pageIds = [];
        $pageCount = max(1, count($pages));

        foreach ($pages as $pageIndex => $pageCommands) {
            $content = $this->pageContent($pageCommands, $pageHeight, $fontRefs, $pageIndex + 1, $pageCount, $pageWidth, $margins);
            $contentId = $writer->stream([], $content);
            $pageIds[] = $writer->add(
                sprintf(
                    '<< /Type /Page /Parent __PAGES__ 0 R /MediaBox [0 0 %.2F %.2F] /Contents %d 0 R /Resources << /Font << %s >> /ProcSet [/PDF /Text /ImageC] /ExtGState << /GS1 << /ca %.3F /CA %.3F >> >> >> >>',
                    $pageWidth,
                    $pageHeight,
                    $contentId,
                    $fontResource,
                    $this->watermarkOpacity,
                    $this->watermarkOpacity
                )
            );
        }

        $kids = implode(' ', array_map(static fn (int $id): string => $id.' 0 R', $pageIds));
        $pagesId = $writer->add('<< /Type /Pages /Kids ['.$kids.'] /Count '.count($pageIds).' >>');

        foreach ($pageIds as $pageId) {
            $writer->replace($pageId, str_replace('__PAGES__', (string) $pagesId, $writer->get($pageId)));
        }

        $info = $this->metadata + [
            'Title' => $this->metadata['title'] ?? 'Unicode Document',
            'Author' => $this->metadata['author'] ?? 'laravel-unicode-pdf',
            'Creator' => $this->metadata['creator'] ?? 'laravel-unicode-pdf native',
            'Producer' => 'imrandevbd/laravel-unicode-pdf',
        ];

        $infoParts = [];
        foreach (['Title', 'Author', 'Subject', 'Keywords', 'Creator', 'Producer'] as $key) {
            $value = $info[$key] ?? $info[strtolower($key)] ?? null;
            if (is_string($value) && $value !== '') {
                $infoParts[] = '/'.$key.' '.PdfWriter::utf16beString($value);
            }
        }
        $infoParts[] = '/CreationDate (D:'.date('YmdHis').')';
        $infoId = $writer->add('<< '.implode(' ', $infoParts).' >>');
        $catalogId = $writer->add('<< /Type /Catalog /Pages '.$pagesId.' 0 R >>');

        $pdf = $writer->finish($catalogId, $infoId);

        // Keep source Unicode as a comment *before* startxref so the trailer stays valid.
        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($sourceHtml)) ?? '');
        if ($plain !== '') {
            $pdf = preg_replace('/\nstartxref\n/', "\n% ".$plain."\nstartxref\n", $pdf, 1) ?? $pdf;
        }

        return $pdf;
    }

    protected function embedFont(PdfWriter $writer, TtfFont $font): int
    {
        $fontFileId = $writer->stream(
            ['Length1' => strlen($font->raw)],
            $font->raw
        );

        $bbox = array_map(
            static fn (int $v): int => (int) round($v * 1000 / $font->unitsPerEm),
            $font->bbox
        );
        $ascent = (int) round($font->ascent * 1000 / $font->unitsPerEm);
        $descent = (int) round($font->descent * 1000 / $font->unitsPerEm);
        $base = preg_replace('/[^A-Za-z0-9\-+]/', '', $font->postscriptName) ?: 'Embedded';

        $descriptorId = $writer->add(sprintf(
            '<< /Type /FontDescriptor /FontName /%s /Flags 4 /FontBBox [%d %d %d %d] /ItalicAngle 0 /Ascent %d /Descent %d /CapHeight %d /StemV 80 /FontFile2 %d 0 R >>',
            $base,
            $bbox[0],
            $bbox[1],
            $bbox[2],
            $bbox[3],
            $ascent,
            $descent,
            $ascent,
            $fontFileId
        ));

        $widths = [];
        for ($gid = 0; $gid < min($font->numGlyphs, 65535); $gid++) {
            $widths[] = $font->pdfWidth($gid);
        }
        $wLiteral = '0 ['.implode(' ', $widths).']';

        $cidId = $writer->add(sprintf(
            '<< /Type /Font /Subtype /CIDFontType2 /BaseFont /%s /CIDSystemInfo << /Registry (Adobe) /Ordering (Identity) /Supplement 0 >> /FontDescriptor %d 0 R /DW 1000 /W [%s] /CIDToGIDMap /Identity >>',
            $base,
            $descriptorId,
            $wLiteral
        ));

        $toUnicode = $this->toUnicodeCmap($font);
        $cmapId = $writer->stream([], $toUnicode);

        return $writer->add(sprintf(
            '<< /Type /Font /Subtype /Type0 /BaseFont /%s /Encoding /Identity-H /DescendantFonts [%d 0 R] /ToUnicode %d 0 R >>',
            $base,
            $cidId,
            $cmapId
        ));
    }

    protected function toUnicodeCmap(TtfFont $font): string
    {
        $mappings = [];
        foreach ($font->cmap() as $cp => $gid) {
            if ($gid <= 0) {
                continue;
            }
            // First / smallest codepoint wins so PUA and later aliases do not hide the letter.
            if (! isset($mappings[$gid]) || $cp < $mappings[$gid][0]) {
                $mappings[$gid] = [$cp, sprintf('<%04X> <%s>', $gid, self::utf16BeHex($cp))];
            }
        }
        $mappings = array_map(static fn (array $pair): string => $pair[1], $mappings);

        $chunks = array_chunk(array_values($mappings), 100);
        $body = "/CIDInit /ProcSet findresource begin\n12 dict begin\nbegincmap\n";
        $body .= "/CIDSystemInfo << /Registry (Adobe) /Ordering (UCS) /Supplement 0 >> def\n";
        $body .= "/CMapName /Adobe-Identity-UCS def\n/CMapType 2 def\n";
        $body .= "1 begincodespacerange\n<0000> <FFFF>\nendcodespacerange\n";
        foreach ($chunks as $chunk) {
            $body .= count($chunk)." beginbfchar\n".implode("\n", $chunk)."\nendbfchar\n";
        }
        $body .= "endcmap\nCMapName currentdict /CMap defineresource pop\nend\nend";

        return $body;
    }

    protected static function utf16BeHex(int $codepoint): string
    {
        if ($codepoint <= 0xFFFF) {
            return sprintf('%04X', $codepoint);
        }

        $codepoint -= 0x10000;

        return sprintf('%04X%04X', 0xD800 + ($codepoint >> 10), 0xDC00 + ($codepoint & 0x3FF));
    }

    /**
     * @param  list<array<string, mixed>>  $commands
     * @param  array<string, string>  $fontRefs
     * @param  array{top: float, right: float, bottom: float, left: float}  $margins
     */
    protected function pageContent(
        array $commands,
        float $pageHeight,
        array $fontRefs,
        int $pageNumber,
        int $pageCount,
        float $pageWidth,
        array $margins
    ): string {
        $ops = [];

        if ($this->watermarkText) {
            $ops[] = 'q /GS1 gs BT /F0 48 Tf 0.7 0.7 0.7 rg';
            $ops[] = sprintf('1 0 0 1 %.2F %.2F Tm', $pageWidth / 4, $pageHeight / 2);
            $ops[] = '('.PdfWriter::escapeLiteral($this->watermarkText).') Tj ET Q';
        }

        foreach ($commands as $command) {
            $type = $command['type'] ?? '';
            if ($type === 'text') {
                $pdfY = $pageHeight - (float) $command['y'] - (float) $command['size'];
                $ops[] = 'BT';
                $ops[] = PdfWriter::rgb($command['color']).' rg';
                if (! empty($command['core']) || ! ($command['font'] instanceof TtfFont)) {
                    $ops[] = sprintf('/F0 %.2F Tf', $command['size']);
                    $ops[] = sprintf('1 0 0 1 %.2F %.2F Tm', $command['x'], $pdfY);
                    $ops[] = '('.PdfWriter::escapeLiteral((string) $command['text']).') Tj';
                } else {
                    $alias = $fontRefs[$command['font']->path] ?? 'F0';
                    $ops[] = sprintf('/%s %.2F Tf', $alias, $command['size']);
                    $positions = $command['positions'] ?? [];
                    if ($positions !== []) {
                        $size = (float) $command['size'];
                        foreach ($positions as $pos) {
                            $ops[] = sprintf(
                                '1 0 0 1 %.2F %.2F Tm',
                                (float) $command['x'] + ((float) $pos['x'] * $size),
                                $pdfY + ((float) $pos['y'] * $size)
                            );
                            $ops[] = PdfWriter::hexGids([(int) $pos['gid']]).' Tj';
                        }
                    } else {
                        $ops[] = sprintf('1 0 0 1 %.2F %.2F Tm', $command['x'], $pdfY);
                        $ops[] = PdfWriter::hexGids($command['gids']).' Tj';
                    }
                }
                $ops[] = 'ET';
            } elseif ($type === 'rect') {
                $pdfY = $pageHeight - (float) $command['y'] - (float) $command['h'];
                $ops[] = PdfWriter::rgb($command['fill']).' rg';
                $ops[] = sprintf('%.2F %.2F %.2F %.2F re f', $command['x'], $pdfY, $command['w'], $command['h']);
                if (isset($command['stroke'])) {
                    $ops[] = PdfWriter::rgb($command['stroke']).' RG';
                    $ops[] = '0.4 w';
                    $ops[] = sprintf('%.2F %.2F %.2F %.2F re S', $command['x'], $pdfY, $command['w'], $command['h']);
                }
            } elseif ($type === 'line') {
                $ops[] = PdfWriter::rgb($command['color'] ?? [0.5, 0.5, 0.5]).' RG';
                $ops[] = sprintf('%.2F w', $command['width'] ?? 0.5);
                $ops[] = sprintf(
                    '%.2F %.2F m %.2F %.2F l S',
                    $command['x1'],
                    $pageHeight - (float) $command['y1'],
                    $command['x2'],
                    $pageHeight - (float) $command['y2']
                );
            }
        }

        if ($this->headerHtml) {
            $header = trim(strip_tags($this->headerHtml));
            if ($header !== '') {
                $ops[] = sprintf('BT /F0 9 Tf 0.3 0.3 0.3 rg 1 0 0 1 %.2F %.2F Tm (%s) Tj ET', $margins['left'], $pageHeight - 20, PdfWriter::escapeLiteral($header));
            }
        }

        $footer = $this->footerHtml ? trim(strip_tags($this->footerHtml)) : '';
        if ($this->pageNumberFormat) {
            $footer = str_replace(
                ['{PAGE_NUM}', '{PAGE_COUNT}', '{PAGENO}', '{nbpg}'],
                [(string) $pageNumber, (string) $pageCount, (string) $pageNumber, (string) $pageCount],
                $this->pageNumberFormat
            );
        }
        if ($footer !== '') {
            $ops[] = sprintf('BT /F0 9 Tf 0.3 0.3 0.3 rg 1 0 0 1 %.2F %.2F Tm (%s) Tj ET', $margins['left'], 16, PdfWriter::escapeLiteral($footer));
        }

        return implode("\n", $ops);
    }

    /**
     * @return array{0: float, 1: float}
     */
    protected function pageDimensions(): array
    {
        if (is_array($this->paper) && count($this->paper) >= 2) {
            $width = (float) $this->paper[0];
            $height = (float) $this->paper[1];
        } else {
            $name = is_string($this->paper) ? strtolower($this->paper) : 'a4';
            $size = PaperSize::tryFrom($name);
            [$width, $height] = $size ? $size->dimensions() : PaperSize::A4->dimensions();
        }

        if (str_starts_with(strtolower($this->orientation), 'l')) {
            return [$height, $width];
        }

        return [$width, $height];
    }

    /**
     * @return array{top: float, right: float, bottom: float, left: float}
     */
    protected function marginsInPoints(): array
    {
        $unit = $this->margins['unit'];
        $factor = match ($unit) {
            'pt' => 1.0,
            'in' => 72.0,
            'cm' => 72 / 2.54,
            default => 72 / 25.4,
        };

        return [
            'top' => (float) $this->margins['top'] * $factor,
            'right' => (float) $this->margins['right'] * $factor,
            'bottom' => (float) $this->margins['bottom'] * $factor,
            'left' => (float) $this->margins['left'] * $factor,
        ];
    }

    /**
     * @return list<string>
     */
    protected function fontSearchDirectories(): array
    {
        $dirs = [
            FontLibrary::customFontPath(),
            FontLibrary::packageFontPath(),
        ];

        $configured = config('unicode-pdf.font_path');
        if (is_string($configured) && $configured !== '') {
            $dirs[] = $configured;
        }

        if (function_exists('storage_path')) {
            $dirs[] = storage_path('app/unicode-pdf/fonts');
        }

        return array_values(array_unique($dirs));
    }
}
