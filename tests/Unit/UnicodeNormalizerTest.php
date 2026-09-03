<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Tests\Unit;

use ImranDev\UnicodePdf\Unicode\UnicodeNormalizer;
use PHPUnit\Framework\TestCase;

class UnicodeNormalizerTest extends TestCase
{
    protected UnicodeNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new UnicodeNormalizer;
    }

    public function test_normalizes_empty_string(): void
    {
        $this->assertSame('', $this->normalizer->normalize(''));
    }

    public function test_normalizes_nfc_form(): void
    {
        // Decomposed 'e' + combining acute accent -> composed 'é'
        $decomposed = "e\xCC\x81";
        $composed = 'é';

        $result = $this->normalizer->normalize($decomposed, UnicodeNormalizer::FORM_NFC);

        $this->assertEquals($composed, $result);
    }

    public function test_preserves_bengali_and_arabic_strings(): void
    {
        $bengali = 'বাংলা ভাষা';
        $arabic = 'اللغة العربية';

        $this->assertEquals($bengali, $this->normalizer->normalize($bengali));
        $this->assertEquals($arabic, $this->normalizer->normalize($arabic));
    }
}
