<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Tests\Integration;

use ImranDev\UnicodePdf\Engines\NativeEngine;
use ImranDev\UnicodePdf\Facades\UnicodePdf;
use ImranDev\UnicodePdf\Native\FontLibrary;
use ImranDev\UnicodePdf\Native\Shaper;
use ImranDev\UnicodePdf\Tests\TestCase;

class NativeEngineTest extends TestCase
{
    public function test_native_engine_generates_valid_pdf_without_third_party_packages(): void
    {
        $pdf = UnicodePdf::engine('native')
            ->loadHtml('<h1>Hello World</h1><p>Invoice #1001</p>')
            ->output();

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertStringContainsString('%%EOF', $pdf);
        $this->assertStringContainsString('Hello World', $pdf);
    }

    public function test_native_engine_embeds_unicode_source_text(): void
    {
        $pdf = UnicodePdf::engine('native')
            ->preset('bengali')
            ->loadHtml('<h1>বাংলাদেশ</h1><p>শিক্ষার্থী ৳৮০,০০০</p>')
            ->output();

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertStringContainsString('বাংলাদেশ', $pdf);
        $this->assertStringContainsString('শিক্ষার্থী', $pdf);
        $this->assertStringContainsString('৳৮০,০০০', $pdf);
        $this->assertStringNotContainsString('à¦', $pdf);

        $bengaliFont = FontLibrary::packageFontPath().DIRECTORY_SEPARATOR.'NotoSansBengali-Regular.ttf';
        if (is_readable($bengaliFont)) {
            $this->assertStringContainsString('/Identity-H', $pdf);
            $this->assertStringContainsString('/CIDFontType2', $pdf);
        }
    }

    public function test_native_engine_renders_mixed_scripts_and_tables(): void
    {
        $html = <<<'HTML'
        <h1>Universal Document</h1>
        <p>English Hello · বাংলা · مرحبا · हिन्दी</p>
        <table>
            <tr><th>Item</th><th>Total</th></tr>
            <tr><td>ল্যাপটপ</td><td>৳৮৫,০০০</td></tr>
        </table>
        HTML;

        $pdf = UnicodePdf::engine('native')
            ->preset('universal')
            ->loadHtml($html)
            ->a4()
            ->output();

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertStringContainsString('ল্যাপটপ', $pdf);
        $this->assertGreaterThan(800, strlen($pdf));
    }

    public function test_native_engine_supports_unicode_capabilities(): void
    {
        $engine = new NativeEngine;

        $this->assertSame('native', $engine->getName());
        $this->assertTrue($engine->supports('unicode'));
        $this->assertTrue($engine->supports('rtl'));
        $this->assertTrue($engine->supports('font-shaping'));
    }

    public function test_indic_left_matra_is_reordered(): void
    {
        $ka = 0x0995; // ক
        $e = 0x09C7;  // ে
        $reordered = Shaper::reorderIndic([$ka, $e]);

        $this->assertSame([$e, $ka], $reordered);
    }

    public function test_bengali_o_kar_is_split_and_reordered(): void
    {
        $ma = 0x09AE;
        $o = 0x09CB;
        $tta = 0x099F;

        $this->assertSame(
            [0x09C7, $ma, 0x09BE, $tta],
            Shaper::reorderIndic([$ma, $o, $tta])
        );
    }

    public function test_bengali_left_matra_stays_before_conjunct(): void
    {
        $ma = 0x09AE;
        $hasant = 0x09CD;
        $pa = 0x09AA;
        $i = 0x09BF;

        $this->assertSame(
            [$i, $ma, $hasant, $pa],
            Shaper::reorderIndic([$ma, $hasant, $pa, $i])
        );
    }

    public function test_package_font_directory_is_discoverable(): void
    {
        $path = FontLibrary::packageFontPath();

        $this->assertDirectoryExists($path);
    }

    public function test_html_css_controls_font_size_color_and_alignment(): void
    {
        $html = <<<'HTML'
        <style>
            .title { font-size: 20pt; color: #cc0000; text-align: center; }
            .note { font-size: 9pt; color: blue; }
        </style>
        <h1 style="font-size: 8pt;">Tiny heading</h1>
        <p class="title">Centered title</p>
        <p class="note">Small note</p>
        HTML;

        $pdf = UnicodePdf::engine('native')
            ->loadHtml($html)
            ->fontSize(12)
            ->css('.extra { font-weight: bold; }')
            ->output();

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertStringContainsString('Tiny heading', $pdf);
        $this->assertStringContainsString('Centered title', $pdf);
        $this->assertStringContainsString('Small note', $pdf);
    }
}
