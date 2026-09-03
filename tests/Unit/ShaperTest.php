<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Tests\Unit;

use ImranDev\UnicodePdf\Native\FontLibrary;
use ImranDev\UnicodePdf\Native\Shaper;
use ImranDev\UnicodePdf\Native\TtfFont;
use ImranDev\UnicodePdf\Tests\TestCase;

class ShaperTest extends TestCase
{
    public function test_bengali_o_kar_is_not_replaced_with_virama(): void
    {
        $font = $this->bengaliFont();
        if ($font === null) {
            $this->markTestSkipped('A Bengali TTF is not installed.');
        }

        $shaped = Shaper::shape('মোট', $font, false);
        $virama = $font->glyphId(0x09CD);

        $this->assertNotContains($virama, $shaped['gids']);
        $this->assertLessThanOrEqual(4, count($shaped['gids']));
    }

    public function test_ai_borno_composes_o_kar(): void
    {
        $path = FontLibrary::packageFontPath().DIRECTORY_SEPARATOR.'AI-Borno-Regular.ttf';
        if (! is_readable($path)) {
            $this->markTestSkipped('AI-Borno is not installed.');
        }

        $font = TtfFont::load($path);
        $shaped = Shaper::shape('মোট', $font, false);

        $this->assertSame(2, count($shaped['gids']));
    }

    public function test_bengali_ra_phala_composes_ka_virama_ra(): void
    {
        $font = $this->bengaliFont();
        if ($font === null) {
            $this->markTestSkipped('A Bengali TTF is not installed.');
        }

        $kra = Shaper::shape('ক্র', $font, false);
        $this->assertSame(1, count($kra['gids']));

        $bikroy = Shaper::shape('বিক্রয়', $font, false);
        $ra = $font->glyphId(0x09B0);
        $this->assertLessThanOrEqual(3, count($bikroy['gids']));
        $this->assertNotContains($ra, $bikroy['gids']);

        $buyer = Shaper::shape('ক্রেতার', $font, false);
        $this->assertLessThanOrEqual(4, count($buyer['gids']));
    }

    public function test_bengali_ja_phala_composes_na_virama_ya(): void
    {
        $font = $this->bengaliFont();
        if ($font === null) {
            $this->markTestSkipped('A Bengali TTF is not installed.');
        }

        $nya = Shaper::shape('ন্য', $font, false);
        $this->assertSame(1, count($nya['gids']));

        $thanks = Shaper::shape('ধন্যবাদ', $font, false);
        $ya = $font->glyphId(0x09AF);
        $this->assertLessThanOrEqual(5, count($thanks['gids']));
        $this->assertNotContains($ya, $thanks['gids']);

        $laptop = Shaper::shape('ল্যাপটপ', $font, false);
        $this->assertLessThan(6, count($laptop['gids']));
        $this->assertNotContains($ya, $laptop['gids']);
    }

    public function test_bengali_conjuncts_reduce_glyph_count(): void
    {
        $font = $this->bengaliFont();
        if ($font === null) {
            $this->markTestSkipped('A Bengali TTF is not installed.');
        }

        $computer = Shaper::shape('কম্পিউটার', $font, false);
        $this->assertLessThan(9, count($computer['gids']));

        $address = Shaper::shape('ঠিকানা', $font, false);
        $this->assertLessThan(6, count($address['gids']));

        $thanks = Shaper::shape('ধন্যবাদ', $font, false);
        $this->assertLessThan(7, count($thanks['gids']));
    }

    public function test_bengali_e_kar_does_not_jump_to_previous_letter(): void
    {
        $font = $this->bengaliFont();
        if ($font === null) {
            $this->markTestSkipped('A Bengali TTF is not installed.');
        }

        $pe = Shaper::shape('পে', $font, false);
        $porer = Shaper::shape('পরের', $font, false);
        $sore = Shaper::shape('সরে', $font, false);
        $se = Shaper::shape('সে', $font, false);

        $this->assertNotSame($pe['gids'][0] ?? 0, $porer['gids'][0] ?? 0, 'পরের must not start with পে');
        $this->assertGreaterThanOrEqual(3, count($porer['gids']));
        $this->assertNotSame($se['gids'][0] ?? 0, $sore['gids'][0] ?? 0, 'সরে must not start with সে');

        $jigges = Shaper::shape('জিজ্ঞেস', $font, false);
        $se = Shaper::shape('সে', $font, false);
        $this->assertLessThanOrEqual(4, count($jigges['gids']));
        $this->assertNotSame($se['gids'][0] ?? 0, $jigges['gids'][array_key_last($jigges['gids'])] ?? 0, 'জিজ্ঞেস must not end with সে');
    }

    public function test_devanagari_i_matra_is_reordered_before_consonant(): void
    {
        $ka = 0x0915;
        $i = 0x093F;

        $this->assertSame([$i, $ka], Shaper::reorderIndic([$ka, $i]));
    }

    public function test_hindi_i_matra_stays_left_and_kra_composes(): void
    {
        $path = FontLibrary::packageFontPath().DIRECTORY_SEPARATOR.'NotoSansDevanagari-Regular.ttf';
        if (! is_readable($path)) {
            $this->markTestSkipped('Noto Sans Devanagari is not installed.');
        }

        $font = TtfFont::load($path);
        $i = $font->glyphId(0x093F);
        $ba = $font->glyphId(0x092C);
        $ra = $font->glyphId(0x0930);

        $bi = Shaper::shape('बि', $font, false);
        $this->assertSame($i, $bi['gids'][0] ?? 0);
        $this->assertSame($ba, $bi['gids'][1] ?? 0);

        $kra = Shaper::shape('क्र', $font, false);
        $this->assertSame(1, count($kra['gids']));
        $this->assertNotContains($ra, $kra['gids']);

        $bikri = Shaper::shape('बिक्री', $font, false);
        $this->assertSame($i, $bikri['gids'][0] ?? 0);
        $this->assertNotContains($ra, $bikri['gids']);

        $ii = $font->glyphId(0x0940);
        $ki = Shaper::shape('की', $font, false);
        $this->assertContains($ii, $ki['gids'], 'की must keep the default ी, not a contextual PUA alt');
    }

    public function test_hindi_conjuncts_do_not_leave_virama(): void
    {
        $path = FontLibrary::packageFontPath().DIRECTORY_SEPARATOR.'NotoSansDevanagari-Regular.ttf';
        if (! is_readable($path)) {
            $this->markTestSkipped('Noto Sans Devanagari is not installed.');
        }

        $font = TtfFont::load($path);
        $virama = $font->glyphId(0x094D);

        foreach (['हिन्दी', 'स्वागत', 'बिक्री', 'क्रिया', 'विद्यालय', 'क्ष', 'ज्ञ'] as $word) {
            $shaped = Shaper::shape($word, $font, false);
            $this->assertNotContains($virama, $shaped['gids'], $word.' left a raw virama');
            $this->assertNotSame([], $shaped['gids']);
        }
    }

    protected function bengaliFont(): ?TtfFont
    {
        foreach (['AI-Borno-Regular.ttf', 'NotoSansBengali-Regular.ttf'] as $file) {
            $path = FontLibrary::packageFontPath().DIRECTORY_SEPARATOR.$file;
            if (is_readable($path)) {
                return TtfFont::load($path);
            }
        }

        $custom = FontLibrary::customFontPath().DIRECTORY_SEPARATOR.'AI-Borno-Regular.ttf';
        if (is_readable($custom)) {
            return TtfFont::load($custom);
        }

        return null;
    }
}
