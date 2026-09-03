<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Tests\Unit;

use ImranDev\UnicodePdf\Fonts\FontResolver;
use PHPUnit\Framework\TestCase;

class FontResolverTest extends TestCase
{
    protected FontResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new FontResolver;
    }

    public function test_resolves_bengali_font_when_bengali_present(): void
    {
        $resolved = $this->resolver->resolve('বাংলা পণ্য তালিকা', 'Noto Sans');

        $this->assertContains('Noto Sans', $resolved);
        $this->assertContains('AI-Borno', $resolved);
    }

    public function test_resolves_arabic_and_devanagari_fonts(): void
    {
        $text = 'مرحباً و नमस्ते';
        $resolved = $this->resolver->resolve($text, 'Noto Sans');

        $this->assertContains('Noto Sans Arabic', $resolved);
        $this->assertContains('Noto Sans Devanagari', $resolved);
    }

    public function test_builds_font_family_stack_string(): void
    {
        $fonts = ['Noto Sans', 'Noto Sans Bengali', 'Noto Sans Arabic'];
        $stack = $this->resolver->buildFontFamilyStack($fonts, 'sans-serif');

        $this->assertEquals('"Noto Sans", "Noto Sans Bengali", "Noto Sans Arabic", sans-serif', $stack);
    }
}
