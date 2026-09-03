<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Tests\Unit;

use ImranDev\UnicodePdf\Unicode\ScriptDetector;
use PHPUnit\Framework\TestCase;

class ScriptDetectorTest extends TestCase
{
    protected ScriptDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new ScriptDetector;
    }

    public function test_detects_bengali_script(): void
    {
        $text = 'বাংলা ভাষা ও সাহিত্য এবং কৃষ্ণ ও শিক্ষার্থী';
        $detected = $this->detector->detect($text);

        $this->assertArrayHasKey('Bengali', $detected);
        $this->assertEquals('Bengali', $this->detector->getDominantScript($text));
        $this->assertTrue($this->detector->containsScript($text, 'Bengali'));
    }

    public function test_detects_arabic_script(): void
    {
        $text = 'مرحباً بالعالم — نظام الفواتير';
        $detected = $this->detector->detect($text);

        $this->assertArrayHasKey('Arabic', $detected);
        $this->assertEquals('Arabic', $this->detector->getDominantScript($text));
        $this->assertTrue($this->detector->containsScript($text, 'Arabic'));
    }

    public function test_detects_devanagari_hindi_script(): void
    {
        $text = 'नमस्ते दुनिया और प्रौद्योगिकी';
        $detected = $this->detector->detect($text);

        $this->assertArrayHasKey('Devanagari', $detected);
        $this->assertEquals('Devanagari', $this->detector->getDominantScript($text));
        $this->assertTrue($this->detector->containsScript($text, 'Devanagari'));
    }

    public function test_detects_cjk_chinese_japanese_korean(): void
    {
        $zh = '世界你好，欢迎来到系统';
        $ja = 'こんにちは世界';
        $ko = '안녕하세요 세계';

        $this->assertTrue($this->detector->containsScript($zh, 'CJK'));
        $this->assertTrue($this->detector->containsScript($ja, 'Japanese'));
        $this->assertTrue($this->detector->containsScript($ko, 'Korean'));
        $this->assertTrue($this->detector->containsScript('ភាសាខ្មែរ', 'Khmer'));
        $this->assertTrue($this->detector->containsScript('မြန်မာ', 'Myanmar'));
        $this->assertTrue($this->detector->containsComplexScript('ភាសាខ្មែរ'));
    }

    public function test_detects_complex_scripts(): void
    {
        $this->assertTrue($this->detector->containsComplexScript('বাংলাদেশ'));
        $this->assertTrue($this->detector->containsComplexScript('مرحبا'));
        $this->assertTrue($this->detector->containsComplexScript('भारत'));
        $this->assertFalse($this->detector->containsComplexScript('Hello World English Only'));
    }

    public function test_detects_multiple_scripts_in_mixed_text(): void
    {
        $mixed = 'English text with বাংলা শব্দ and العربية كلمة and हिन्दी शब्द';
        $detected = $this->detector->detect($mixed);

        $this->assertArrayHasKey('Latin', $detected);
        $this->assertArrayHasKey('Bengali', $detected);
        $this->assertArrayHasKey('Arabic', $detected);
        $this->assertArrayHasKey('Devanagari', $detected);
    }
}
