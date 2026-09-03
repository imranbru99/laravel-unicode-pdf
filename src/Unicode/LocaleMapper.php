<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Unicode;

class LocaleMapper
{
    /**
     * Map of locale codes to script, direction, and suggested font family.
     *
     * @var array<string, array{script: string, direction: string, font: string}>
     */
    protected static array $locales = [
        'bn' => ['script' => 'Bengali', 'direction' => 'ltr', 'font' => 'AI-Borno'],
        'as' => ['script' => 'Bengali', 'direction' => 'ltr', 'font' => 'AI-Borno'],
        'ar' => ['script' => 'Arabic', 'direction' => 'rtl', 'font' => 'Noto Sans Arabic'],
        'ur' => ['script' => 'Arabic', 'direction' => 'rtl', 'font' => 'Noto Nastaliq Urdu'],
        'fa' => ['script' => 'Arabic', 'direction' => 'rtl', 'font' => 'Noto Sans Arabic'],
        'ps' => ['script' => 'Arabic', 'direction' => 'rtl', 'font' => 'Noto Sans Arabic'],
        'sd' => ['script' => 'Arabic', 'direction' => 'rtl', 'font' => 'Noto Sans Arabic'],
        'he' => ['script' => 'Hebrew', 'direction' => 'rtl', 'font' => 'Noto Sans Hebrew'],
        'yi' => ['script' => 'Hebrew', 'direction' => 'rtl', 'font' => 'Noto Sans Hebrew'],
        'dv' => ['script' => 'Thaana', 'direction' => 'rtl', 'font' => 'Noto Sans Thaana'],
        'hi' => ['script' => 'Devanagari', 'direction' => 'ltr', 'font' => 'Noto Sans Devanagari'],
        'mr' => ['script' => 'Devanagari', 'direction' => 'ltr', 'font' => 'Noto Sans Devanagari'],
        'ne' => ['script' => 'Devanagari', 'direction' => 'ltr', 'font' => 'Noto Sans Devanagari'],
        'ta' => ['script' => 'Tamil', 'direction' => 'ltr', 'font' => 'Noto Sans Tamil'],
        'te' => ['script' => 'Telugu', 'direction' => 'ltr', 'font' => 'Noto Sans Telugu'],
        'ml' => ['script' => 'Malayalam', 'direction' => 'ltr', 'font' => 'Noto Sans Malayalam'],
        'gu' => ['script' => 'Gujarati', 'direction' => 'ltr', 'font' => 'Noto Sans Gujarati'],
        'pa' => ['script' => 'Gurmukhi', 'direction' => 'ltr', 'font' => 'Noto Sans Gurmukhi'],
        'kn' => ['script' => 'Kannada', 'direction' => 'ltr', 'font' => 'Noto Sans Kannada'],
        'or' => ['script' => 'Odia', 'direction' => 'ltr', 'font' => 'Noto Sans Oriya'],
        'si' => ['script' => 'Sinhala', 'direction' => 'ltr', 'font' => 'Noto Sans Sinhala'],
        'th' => ['script' => 'Thai', 'direction' => 'ltr', 'font' => 'Noto Sans Thai'],
        'lo' => ['script' => 'Lao', 'direction' => 'ltr', 'font' => 'Noto Sans Lao'],
        'km' => ['script' => 'Khmer', 'direction' => 'ltr', 'font' => 'Noto Sans Khmer'],
        'my' => ['script' => 'Myanmar', 'direction' => 'ltr', 'font' => 'Noto Sans Myanmar'],
        'bo' => ['script' => 'Tibetan', 'direction' => 'ltr', 'font' => 'Noto Sans Tibetan'],
        'am' => ['script' => 'Ethiopic', 'direction' => 'ltr', 'font' => 'Noto Sans Ethiopic'],
        'ti' => ['script' => 'Ethiopic', 'direction' => 'ltr', 'font' => 'Noto Sans Ethiopic'],
        'zh' => ['script' => 'CJK', 'direction' => 'ltr', 'font' => 'Noto Sans CJK SC'],
        'zh-CN' => ['script' => 'CJK', 'direction' => 'ltr', 'font' => 'Noto Sans CJK SC'],
        'zh-TW' => ['script' => 'CJK', 'direction' => 'ltr', 'font' => 'Noto Sans CJK TC'],
        'zh-HK' => ['script' => 'CJK', 'direction' => 'ltr', 'font' => 'Noto Sans CJK TC'],
        'ja' => ['script' => 'Japanese', 'direction' => 'ltr', 'font' => 'Noto Sans CJK JP'],
        'ko' => ['script' => 'Korean', 'direction' => 'ltr', 'font' => 'Noto Sans CJK KR'],
        'ru' => ['script' => 'Cyrillic', 'direction' => 'ltr', 'font' => 'Noto Sans'],
        'uk' => ['script' => 'Cyrillic', 'direction' => 'ltr', 'font' => 'Noto Sans'],
        'bg' => ['script' => 'Cyrillic', 'direction' => 'ltr', 'font' => 'Noto Sans'],
        'sr' => ['script' => 'Cyrillic', 'direction' => 'ltr', 'font' => 'Noto Sans'],
        'el' => ['script' => 'Greek', 'direction' => 'ltr', 'font' => 'Noto Sans'],
        'hy' => ['script' => 'Armenian', 'direction' => 'ltr', 'font' => 'Noto Sans Armenian'],
        'ka' => ['script' => 'Georgian', 'direction' => 'ltr', 'font' => 'Noto Sans Georgian'],
        'vi' => ['script' => 'Latin', 'direction' => 'ltr', 'font' => 'Noto Sans'],
        'en' => ['script' => 'Latin', 'direction' => 'ltr', 'font' => 'Noto Sans'],
        'es' => ['script' => 'Latin', 'direction' => 'ltr', 'font' => 'Noto Sans'],
        'fr' => ['script' => 'Latin', 'direction' => 'ltr', 'font' => 'Noto Sans'],
        'de' => ['script' => 'Latin', 'direction' => 'ltr', 'font' => 'Noto Sans'],
        'pt' => ['script' => 'Latin', 'direction' => 'ltr', 'font' => 'Noto Sans'],
        'tr' => ['script' => 'Latin', 'direction' => 'ltr', 'font' => 'Noto Sans'],
        'id' => ['script' => 'Latin', 'direction' => 'ltr', 'font' => 'Noto Sans'],
        'ms' => ['script' => 'Latin', 'direction' => 'ltr', 'font' => 'Noto Sans'],
        'jv' => ['script' => 'Javanese', 'direction' => 'ltr', 'font' => 'Noto Sans Javanese'],
    ];

    /**
     * Get locale information.
     *
     * @return array{script: string, direction: string, font: string}|null
     */
    public static function get(string $locale): ?array
    {
        $primary = explode('_', str_replace('-', '_', $locale))[0];

        return self::$locales[$locale] ?? self::$locales[$primary] ?? null;
    }

    /**
     * Get suggested font for a locale.
     */
    public static function getFont(string $locale, string $default = 'Noto Sans'): string
    {
        $info = self::get($locale);

        return $info['font'] ?? $default;
    }

    /**
     * Get text direction for a locale ('ltr' or 'rtl').
     */
    public static function getDirection(string $locale): string
    {
        $info = self::get($locale);

        return $info['direction'] ?? 'ltr';
    }

    /**
     * Get script for a locale.
     */
    public static function getScript(string $locale): string
    {
        $info = self::get($locale);

        return $info['script'] ?? 'Latin';
    }

    /**
     * Register or override a locale mapping.
     */
    public static function register(string $locale, string $script, string $direction, string $font): void
    {
        self::$locales[$locale] = [
            'script' => $script,
            'direction' => $direction,
            'font' => $font,
        ];
    }
}
