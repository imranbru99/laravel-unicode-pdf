<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Unicode;

use ImranDev\UnicodePdf\Contracts\DirectionDetectorInterface;

class DirectionDetector implements DirectionDetectorInterface
{
    /**
     * Regex matching RTL scripts: Arabic, Hebrew, Syriac, Thaana, Samaritan, Mandaic, etc.
     */
    /**
     * RTL scripts: Hebrew, Arabic, Syriac, Thaana, NKo, Samaritan, Mandaic, Adlam, and presentation forms.
     */
    protected const RTL_PATTERN = '/[\x{0590}-\x{05FF}\x{0600}-\x{06FF}\x{0700}-\x{074F}\x{0750}-\x{077F}\x{0780}-\x{07BF}\x{07C0}-\x{07FF}\x{0800}-\x{083F}\x{0840}-\x{085F}\x{08A0}-\x{08FF}\x{FB1D}-\x{FB4F}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}\x{1E900}-\x{1E95F}]/u';

    /**
     * Regex matching LTR scripts.
     */
    protected const LTR_PATTERN = '/[a-zA-Z\x{00C0}-\x{024F}\x{0400}-\x{04FF}\x{0370}-\x{03FF}\x{0980}-\x{09FF}\x{0900}-\x{097F}\x{4E00}-\x{9FFF}\x{AC00}-\x{D7AF}]/u';

    /**
     * Detect text direction: 'rtl' or 'ltr'.
     */
    public function detect(string $text): string
    {
        // First check explicit HTML dir attribute on top level tags if present
        if (preg_match('/<(?:html|body|main|div)[^>]*\sdir=["\'](rtl|ltr)["\']/i', $text, $matches)) {
            return strtolower($matches[1]);
        }

        $clean = strip_tags($text);
        if (trim($clean) === '') {
            return 'ltr';
        }

        $rtlMatches = preg_match_all(self::RTL_PATTERN, $clean);
        $ltrMatches = preg_match_all(self::LTR_PATTERN, $clean);

        $rtlCount = $rtlMatches !== false ? $rtlMatches : 0;
        $ltrCount = $ltrMatches !== false ? $ltrMatches : 0;

        if ($rtlCount > 0 && $rtlCount >= $ltrCount) {
            return 'rtl';
        }

        return 'ltr';
    }

    /**
     * Check if text contains RTL characters.
     */
    public function isRtl(string $text): bool
    {
        $clean = strip_tags($text);

        return (bool) preg_match(self::RTL_PATTERN, $clean);
    }

    /**
     * Check if text contains both RTL and LTR characters.
     */
    public function isMixed(string $text): bool
    {
        $clean = strip_tags($text);

        $hasRtl = (bool) preg_match(self::RTL_PATTERN, $clean);
        $hasLtr = (bool) preg_match(self::LTR_PATTERN, $clean);

        return $hasRtl && $hasLtr;
    }
}
