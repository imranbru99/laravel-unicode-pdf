<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Tests\Unit;

use ImranDev\UnicodePdf\Support\NumeralConverter;
use PHPUnit\Framework\TestCase;

class NumeralConverterTest extends TestCase
{
    public function test_converts_western_digits_to_bengali(): void
    {
        $this->assertSame('৮৫০০০', NumeralConverter::toBengali('85000'));
        $this->assertSame('৳৮৫,০০০', NumeralConverter::convert('৳85,000', 'bn'));
    }

    public function test_converts_to_arabic_indic_and_persian(): void
    {
        $this->assertSame('١٢٣٤٥', NumeralConverter::toArabicIndic('12345'));
        $this->assertSame('۱۲۳۴۵', NumeralConverter::toPersian('12345'));
        $this->assertNotSame(NumeralConverter::toArabicIndic('4'), NumeralConverter::toPersian('4'));
    }

    public function test_converts_to_devanagari_and_thai(): void
    {
        $this->assertSame('१२३', NumeralConverter::toDevanagari('123'));
        $this->assertSame('๑๒๓', NumeralConverter::convert('123', 'th'));
    }

    public function test_round_trips_to_latin(): void
    {
        $this->assertSame('85000', NumeralConverter::toLatin('৮৫০০০', 'bn'));
        $this->assertSame('12345', NumeralConverter::toLatin('١٢٣٤٥', 'ar'));
    }

    public function test_formats_with_grouping(): void
    {
        $this->assertSame('৮৫,০০০', NumeralConverter::format(85000, 'bn'));
    }

    public function test_unknown_locale_keeps_latin_digits(): void
    {
        $this->assertSame('123', NumeralConverter::convert('123', 'en'));
        $this->assertSame('123', NumeralConverter::convert('123', 'zz'));
    }
}
