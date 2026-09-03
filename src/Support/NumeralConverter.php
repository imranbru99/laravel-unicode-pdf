<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Support;

class NumeralConverter
{
    /**
     * Digit maps keyed by locale / script alias.
     *
     * @var array<string, list<string>>
     */
    protected static array $digits = [
        'latn' => ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
        'bn' => ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'],
        'ar' => ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'],
        'fa' => ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'],
        'hi' => ['०', '१', '२', '३', '४', '५', '६', '७', '८', '९'],
        'ta' => ['௦', '௧', '௨', '௩', '௪', '௫', '௬', '௭', '௮', '௯'],
        'te' => ['౦', '౧', '౨', '౩', '౪', '౫', '౬', '౭', '౮', '౯'],
        'ml' => ['൦', '൧', '൨', '൩', '൪', '൫', '൬', '൭', '൮', '൯'],
        'gu' => ['૦', '૧', '૨', '૩', '૪', '૫', '૬', '૭', '૮', '૯'],
        'pa' => ['੦', '੧', '੨', '੩', '੪', '੫', '੬', '੭', '੮', '੯'],
        'kn' => ['೦', '೧', '೨', '೩', '೪', '೫', '೬', '೭', '೮', '೯'],
        'si' => ['෦', '෧', '෨', '෩', '෪', '෫', '෬', '෭', '෮', '෯'],
        'th' => ['๐', '๑', '๒', '๓', '๔', '๕', '๖', '๗', '๘', '๙'],
        'lo' => ['໐', '໑', '໒', '໓', '໔', '໕', '໖', '໗', '໘', '໙'],
        'my' => ['၀', '၁', '၂', '၃', '၄', '၅', '၆', '၇', '၈', '၉'],
        'km' => ['០', '១', '២', '៣', '៤', '៥', '៦', '៧', '៨', '៩'],
    ];

    /**
     * Locale aliases that share a digit set.
     *
     * @var array<string, string>
     */
    protected static array $aliases = [
        'bengali' => 'bn',
        'bangla' => 'bn',
        'arabic' => 'ar',
        'arabic-indic' => 'ar',
        'persian' => 'fa',
        'farsi' => 'fa',
        'ur' => 'ar',
        'urdu' => 'ar',
        'devanagari' => 'hi',
        'hindi' => 'hi',
        'mr' => 'hi',
        'ne' => 'hi',
        'tamil' => 'ta',
        'telugu' => 'te',
        'malayalam' => 'ml',
        'gujarati' => 'gu',
        'gurmukhi' => 'pa',
        'kannada' => 'kn',
        'sinhala' => 'si',
        'thai' => 'th',
        'lao' => 'lo',
        'myanmar' => 'my',
        'burmese' => 'my',
        'khmer' => 'km',
        'en' => 'latn',
        'latin' => 'latn',
        'western' => 'latn',
    ];

    /**
     * Convert Western digits in a string to a locale's native numerals.
     */
    public static function convert(string $value, string $locale): string
    {
        $key = self::resolve($locale);
        $digits = self::$digits[$key] ?? self::$digits['latn'];

        if ($key === 'latn') {
            return $value;
        }

        return strtr($value, array_combine(self::$digits['latn'], $digits) ?: []);
    }

    /**
     * Convert native numerals back to Western digits.
     */
    public static function toLatin(string $value, ?string $fromLocale = null): string
    {
        if ($fromLocale !== null) {
            $key = self::resolve($fromLocale);
            $digits = self::$digits[$key] ?? null;

            if ($digits && $key !== 'latn') {
                return strtr($value, array_combine($digits, self::$digits['latn']) ?: []);
            }
        }

        foreach (self::$digits as $key => $digits) {
            if ($key === 'latn') {
                continue;
            }

            $value = strtr($value, array_combine($digits, self::$digits['latn']) ?: []);
        }

        return $value;
    }

    public static function toBengali(string $value): string
    {
        return self::convert($value, 'bn');
    }

    public static function toArabicIndic(string $value): string
    {
        return self::convert($value, 'ar');
    }

    public static function toPersian(string $value): string
    {
        return self::convert($value, 'fa');
    }

    public static function toDevanagari(string $value): string
    {
        return self::convert($value, 'hi');
    }

    /**
     * Format a number with grouping and native digits for a locale.
     */
    public static function format(int|float|string $number, string $locale, int $decimals = 0): string
    {
        $formatted = is_numeric($number)
            ? number_format((float) $number, $decimals)
            : (string) $number;

        return self::convert($formatted, $locale);
    }

    protected static function resolve(string $locale): string
    {
        $locale = strtolower(str_replace('_', '-', $locale));
        $primary = explode('-', $locale)[0];

        if (isset(self::$digits[$locale])) {
            return $locale;
        }

        if (isset(self::$digits[$primary])) {
            return $primary;
        }

        return self::$aliases[$locale] ?? self::$aliases[$primary] ?? 'latn';
    }
}
