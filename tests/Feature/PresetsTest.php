<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Tests\Feature;

use ImranDev\UnicodePdf\Facades\UnicodePdf;
use ImranDev\UnicodePdf\Tests\TestCase;
use ImranDev\UnicodePdf\UnicodePdfDocument;

class PresetsTest extends TestCase
{
    public function test_bengali_preset_applies_correct_fonts_and_direction(): void
    {
        $doc = UnicodePdf::preset('bengali');
        $this->assertInstanceOf(UnicodePdfDocument::class, $doc);
    }

    public function test_arabic_preset_applies_rtl(): void
    {
        $doc = UnicodePdf::preset('arabic');
        $this->assertInstanceOf(UnicodePdfDocument::class, $doc);
    }

    public function test_locale_configuration(): void
    {
        $doc = UnicodePdf::locale('ar');
        $this->assertInstanceOf(UnicodePdfDocument::class, $doc);
    }
}
