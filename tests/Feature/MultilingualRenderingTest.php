<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Tests\Feature;

use ImranDev\UnicodePdf\Facades\UnicodePdf;
use ImranDev\UnicodePdf\Tests\TestCase;

class MultilingualRenderingTest extends TestCase
{
    public function test_renders_bengali_complex_text_and_conjuncts(): void
    {
        $html = file_get_contents(__DIR__.'/../Fixtures/Pdf/bengali.html');

        $pdf = UnicodePdf::preset('bengali')
            ->loadHtml($html)
            ->output();

        $this->assertNotEmpty($pdf);
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertStringContainsString('বাংলা', $pdf);
        $this->assertStringContainsString('শিক্ষার্থী', $pdf);
        $this->assertStringContainsString('৳৮০,০০০', $pdf);
    }

    public function test_renders_arabic_rtl_document(): void
    {
        $html = file_get_contents(__DIR__.'/../Fixtures/Pdf/arabic.html');

        $pdf = UnicodePdf::preset('arabic')
            ->loadHtml($html)
            ->output();

        $this->assertNotEmpty($pdf);
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertStringContainsString('مرحباً', $pdf);
        $this->assertStringContainsString('١٠٬٠٠٠', $pdf);
    }

    public function test_renders_hindi_devanagari_document(): void
    {
        $html = file_get_contents(__DIR__.'/../Fixtures/Pdf/hindi.html');

        $pdf = UnicodePdf::preset('indian')
            ->loadHtml($html)
            ->output();

        $this->assertNotEmpty($pdf);
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertStringContainsString('नमस्ते', $pdf);
    }

    public function test_renders_universal_multilingual_document(): void
    {
        $html = file_get_contents(__DIR__.'/../Fixtures/Pdf/mixed.html');

        $pdf = UnicodePdf::preset('universal')
            ->loadHtml($html)
            ->output();

        $this->assertNotEmpty($pdf);
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertStringContainsString('Hello World', $pdf);
        $this->assertStringContainsString('বাংলা', $pdf);
        $this->assertStringContainsString('العالم', $pdf);
        $this->assertStringContainsString('दुनिया', $pdf);
        $this->assertStringContainsString('世界', $pdf);
    }

    public function test_renders_blade_view_with_multilingual_data(): void
    {
        $pdf = UnicodePdf::loadView('unicode-pdf::sample-multilingual', [
            'title' => 'Universal Unicode PDF Test',
            'languages' => [
                'English' => 'Hello World',
                'Bengali' => 'শুভ সকাল বাংলাদেশ',
                'Arabic' => 'مرحباً بالعالم',
                'Hindi' => 'दुनिया में आपका स्वागत है',
                'Urdu' => 'دنیا میں خوش آمدید',
                'Chinese' => '世界你好',
                'Japanese' => 'こんにちは世界',
                'Korean' => '안녕하세요 세계',
                'Russian' => 'Привет мир',
                'Hebrew' => 'שלום עולם',
                'Thai' => 'สวัสดีชาวโลก',
            ],
        ])
            ->setPaper('A4')
            ->output();

        $this->assertNotEmpty($pdf);
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertStringEndsWith('%%EOF', $pdf);
    }

    public function test_renders_full_page_story_in_all_languages(): void
    {
        $stories = require dirname(__DIR__, 2).'/resources/stories/all-languages.php';

        $pdf = UnicodePdf::preset('universal')
            ->loadView('unicode-pdf::story-all-languages', [
                'title' => 'The Book by the River',
                'stories' => $stories,
            ])
            ->setPaper('A4')
            ->output();

        $this->assertNotEmpty($pdf);
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertStringContainsString('The Book by the River', $pdf);
        $this->assertStringContainsString('নদীর পাশে পাওয়া বই', $pdf);
        $this->assertStringContainsString('नदी के किनारे मिली किताब', $pdf);
        $this->assertStringContainsString('الكتاب على ضفة النهر', $pdf);
    }
}
