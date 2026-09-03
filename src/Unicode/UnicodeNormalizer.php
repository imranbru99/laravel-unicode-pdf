<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Unicode;

use Normalizer;

class UnicodeNormalizer
{
    public const FORM_NFC = 'NFC';

    public const FORM_NFD = 'NFD';

    public const FORM_NFKC = 'NFKC';

    public const FORM_NFKD = 'NFKD';

    /**
     * Normalize a Unicode string to the specified normalization form.
     */
    public function normalize(string $string, string $form = self::FORM_NFC): string
    {
        if ($string === '') {
            return '';
        }

        if (! class_exists(Normalizer::class)) {
            return $string;
        }

        $normalizerForm = match (strtoupper($form)) {
            self::FORM_NFD => Normalizer::NFD,
            self::FORM_NFKC => Normalizer::NFKC,
            self::FORM_NFKD => Normalizer::NFKD,
            default => Normalizer::NFC,
        };

        $normalized = Normalizer::normalize($string, $normalizerForm);

        return $normalized !== false ? $normalized : $string;
    }

    /**
     * Check if a string is already normalized in the given form.
     */
    public function isNormalized(string $string, string $form = self::FORM_NFC): bool
    {
        if ($string === '' || ! class_exists(Normalizer::class)) {
            return true;
        }

        $normalizerForm = match (strtoupper($form)) {
            self::FORM_NFD => Normalizer::NFD,
            self::FORM_NFKC => Normalizer::NFKC,
            self::FORM_NFKD => Normalizer::NFKD,
            default => Normalizer::NFC,
        };

        return Normalizer::isNormalized($string, $normalizerForm);
    }
}
