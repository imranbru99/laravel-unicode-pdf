<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Facades;

use Illuminate\Support\Facades\Facade;
use ImranDev\UnicodePdf\Enums\Engine;
use ImranDev\UnicodePdf\Enums\Preset;
use ImranDev\UnicodePdf\Testing\UnicodePdfFake;
use ImranDev\UnicodePdf\UnicodePdfDocument;
use ImranDev\UnicodePdf\UnicodePdfManager;

/**
 * @method static UnicodePdfDocument loadHtml(string $html)
 * @method static UnicodePdfDocument html(string $html)
 * @method static UnicodePdfDocument loadFile(string $path)
 * @method static UnicodePdfDocument loadView(string $view, array $data = [], array $mergeData = [])
 * @method static UnicodePdfDocument view(string $view, array $data = [], array $mergeData = [])
 * @method static UnicodePdfDocument engine(string|Engine|null $name = null)
 * @method static UnicodePdfDocument preset(string|Preset $name)
 * @method static UnicodePdfDocument profile(string $name)
 * @method static UnicodePdfDocument font(string $font)
 * @method static UnicodePdfDocument fallback(array $fonts)
 * @method static UnicodePdfDocument locale(string $locale)
 * @method static UnicodePdfDocument direction(string|\ImranDev\UnicodePdf\Enums\Direction $direction)
 * @method static UnicodePdfDocument bidi(bool $enabled = true)
 * @method static UnicodePdfDocument setPaper(string|array|\ImranDev\UnicodePdf\Enums\PaperSize $paper, string|\ImranDev\UnicodePdf\Enums\Orientation $orientation = 'portrait')
 * @method static UnicodePdfDocument paper(string|array|\ImranDev\UnicodePdf\Enums\PaperSize $paper, string|\ImranDev\UnicodePdf\Enums\Orientation $orientation = 'portrait')
 * @method static UnicodePdfDocument setMargins(int|float $top = 10, int|float $right = 10, int|float $bottom = 10, int|float $left = 10, string $unit = 'mm')
 * @method static UnicodePdfDocument margin(int|float $top = 10, int|float $right = 10, int|float $bottom = 10, int|float $left = 10, string $unit = 'mm')
 * @method static UnicodePdfDocument watermark(string $text, float $opacity = 0.2)
 * @method static UnicodePdfDocument protect(array $options)
 * @method static UnicodePdfDocument encrypt(string $userPassword, ?string $ownerPassword = null, array $permissions = [])
 * @method static UnicodePdfDocument metadata(array $metadata)
 * @method static UnicodePdfDocument title(string $title)
 * @method static UnicodePdfDocument author(string $author)
 * @method static UnicodePdfDocument header(string $html, array $data = [])
 * @method static UnicodePdfDocument footer(string $html, array $data = [])
 * @method static UnicodePdfDocument pageNumbers(string $format = '{PAGE_NUM} / {PAGE_COUNT}')
 * @method static UnicodePdfDocument name(string $filename)
 * @method static UnicodePdfDocument cache(?int $seconds = null, ?string $store = null)
 * @method static void registerFont(array $fontDefinition)
 * @method static bool supports(string $capability)
 * @method static bool validateUtf8(string $text)
 * @method static string normalize(string $text, string $form = 'NFC')
 * @method static string numerals(string $value, string $locale)
 * @method static array detectScripts(string $text)
 * @method static string detectDirection(string $text)
 * @method static array checkGlyphs(string $text, ?string $font = null)
 * @method static bool clearCache()
 * @method static UnicodePdfFake fake()
 * @method static UnicodePdfDocument createDocument(string|Engine|null $engine = null)
 *
 * @see UnicodePdfManager
 */
class UnicodePdf extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'unicode-pdf';
    }

    /**
     * Replace the bound instance with a fake.
     */
    public static function fake(): UnicodePdfFake
    {
        $fake = static::getFacadeRoot()->fake();
        static::swap($fake);

        return $fake;
    }
}
