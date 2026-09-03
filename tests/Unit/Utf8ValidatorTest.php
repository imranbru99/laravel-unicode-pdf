<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Tests\Unit;

use ImranDev\UnicodePdf\Exceptions\InvalidUtf8Exception;
use ImranDev\UnicodePdf\Unicode\Utf8Validator;
use PHPUnit\Framework\TestCase;

class Utf8ValidatorTest extends TestCase
{
    protected Utf8Validator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new Utf8Validator;
    }

    public function test_valid_multilingual_utf8_strings_pass_validation(): void
    {
        $samples = [
            'Hello World',
            'বাংলা ভাষা ও সাহিত্য',
            'مرحباً بالعالم',
            'हिन्दी में आपका स्वागत है',
            '世界你好',
            'こんにちは世界',
            '안녕하세요 세계',
            'Привет мир',
            'שלום עולם',
            'สวัสดีชาวโลก',
            'Γειά σου Κόσমে',
            '👋 🌍 ❤️ 🎉',
            'Order #12345 — বাংলা পণ্য — مرحباً — ৳৮০,০০০',
        ];

        foreach ($samples as $sample) {
            $this->assertTrue($this->validator->validate($sample));
        }
    }

    public function test_invalid_utf8_throws_actionable_exception(): void
    {
        $invalidUtf8 = "Valid text \xC3\x28 with broken byte";

        $this->expectException(InvalidUtf8Exception::class);
        $this->expectExceptionMessage('Invalid UTF-8 byte sequence detected');

        $this->validator->validate($invalidUtf8);
    }

    public function test_validate_returns_false_when_throw_is_false(): void
    {
        $invalidUtf8 = "Invalid \xFF\xFE string";

        $result = $this->validator->validate($invalidUtf8, throw: false);

        $this->assertFalse($result);
    }

    public function test_find_invalid_byte_offset_identifies_exact_index(): void
    {
        $invalid = 'Prefix_'."\x80".'_Suffix';
        $offset = $this->validator->findInvalidByteOffset($invalid);

        $this->assertEquals(7, $offset);
    }
}
