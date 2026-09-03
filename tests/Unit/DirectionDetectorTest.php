<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Tests\Unit;

use ImranDev\UnicodePdf\Unicode\DirectionDetector;
use PHPUnit\Framework\TestCase;

class DirectionDetectorTest extends TestCase
{
    protected DirectionDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new DirectionDetector;
    }

    public function test_detects_ltr_for_english_and_bengali(): void
    {
        $this->assertEquals('ltr', $this->detector->detect('Hello World'));
        $this->assertEquals('ltr', $this->detector->detect('বাংলা ভাষা'));
        $this->assertFalse($this->detector->isRtl('বাংলা ভাষা'));
    }

    public function test_detects_rtl_for_arabic_and_hebrew(): void
    {
        $this->assertEquals('rtl', $this->detector->detect('مرحباً بالعالم'));
        $this->assertTrue($this->detector->isRtl('مرحباً بالعالم'));
        $this->assertTrue($this->detector->isRtl('שלום עולם'));
    }

    public function test_detects_mixed_direction(): void
    {
        $mixed = 'Order #12345 — مرحباً بالعالم';
        $this->assertTrue($this->detector->isMixed($mixed));
    }

    public function test_respects_explicit_html_dir_attribute(): void
    {
        $htmlRtl = '<div dir="rtl">English text forced RTL</div>';
        $htmlLtr = '<div dir="ltr">مرحباً forced LTR</div>';

        $this->assertEquals('rtl', $this->detector->detect($htmlRtl));
        $this->assertEquals('ltr', $this->detector->detect($htmlLtr));
    }
}
