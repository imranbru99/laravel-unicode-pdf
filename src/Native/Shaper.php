<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Native;

class Shaper
{
    /**
     * Right-joining or dual-joining Arabic letters that connect to the next glyph.
     * Dual-joining unless listed in $rightJoiningOnly.
     */
    protected const ARABIC_LETTER = '/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u';

    /**
     * @var array<int, true>
     */
    protected const RIGHT_JOINING = [
        0x0622 => true, 0x0623 => true, 0x0624 => true, 0x0625 => true, 0x0627 => true,
        0x062F => true, 0x0630 => true, 0x0631 => true, 0x0632 => true, 0x0648 => true,
        0x0649 => true, 0x0671 => true, 0x0672 => true, 0x0673 => true, 0x0675 => true,
        0x0688 => true, 0x0689 => true, 0x068A => true, 0x068B => true, 0x068C => true,
        0x068D => true, 0x068E => true, 0x068F => true, 0x0690 => true, 0x0691 => true,
        0x0692 => true, 0x0693 => true, 0x0694 => true, 0x0695 => true, 0x0696 => true,
        0x0697 => true, 0x0698 => true, 0x0699 => true, 0x06C0 => true, 0x06C3 => true,
        0x06C4 => true, 0x06C5 => true, 0x06C6 => true, 0x06C7 => true, 0x06C8 => true,
        0x06C9 => true, 0x06CA => true, 0x06CB => true, 0x06CD => true, 0x06CF => true,
        0x06D2 => true, 0x06D3 => true, 0x06EE => true, 0x06EF => true,
    ];

    /**
     * Combining marks treated as transparent for joining.
     */
    protected const TRANSPARENT = '/[\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}\x{08D3}-\x{08E1}\x{08E3}-\x{08FF}]/u';

    /**
     * Left (pre-base) matras that must be drawn before the consonant.
     *
     * @var array<int, true>
     */
    protected const LEFT_MATRAS = [
        0x093F => true, // Devanagari I
        0x09BF => true, // Bengali I
        0x09C7 => true, // Bengali E
        0x09C8 => true, // Bengali AI
        0x0A3F => true, // Gurmukhi I
        0x0ABF => true, // Gujarati I
        0x0BBF => true, // Tamil I
        0x0BC6 => true, // Tamil E
        0x0BC7 => true, // Tamil EE
        0x0BC8 => true, // Tamil AI
        0x0C46 => true, // Telugu E
        0x0C47 => true, // Telugu EE
        0x0C48 => true, // Telugu AI
        0x0CBF => true, // Kannada I
        0x0CC6 => true, // Kannada E
        0x0CC7 => true, // Kannada EE
        0x0CC8 => true, // Kannada AI
        0x0D46 => true, // Malayalam E
        0x0D47 => true, // Malayalam EE
        0x0D48 => true, // Malayalam AI
        0x0DD9 => true, // Sinhala E
        0x0DDA => true, // Sinhala EE
        0x0DDB => true, // Sinhala AI
        0x0DDC => true, // Sinhala O
        0x0DDD => true, // Sinhala OO
        0x0DDE => true, // Sinhala AU
    ];

    public static function isRtlText(string $text): bool
    {
        return (bool) preg_match(
            '/[\x{0590}-\x{05FF}\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB1D}-\x{FDFF}\x{FE70}-\x{FEFF}]/u',
            $text
        );
    }

    /**
     * Isolated / final / initial / medial presentation forms (U+FB50–U+FEFF).
     *
     * @var array<int, array{isol: int, fina: int, init?: int, medi?: int}>
     */
    protected const PRESENTATION = [
        0x0627 => ['isol' => 0xFE8D, 'fina' => 0xFE8E],
        0x0628 => ['isol' => 0xFE8F, 'fina' => 0xFE90, 'init' => 0xFE91, 'medi' => 0xFE92],
        0x062A => ['isol' => 0xFE95, 'fina' => 0xFE96, 'init' => 0xFE97, 'medi' => 0xFE98],
        0x062B => ['isol' => 0xFE99, 'fina' => 0xFE9A, 'init' => 0xFE9B, 'medi' => 0xFE9C],
        0x062C => ['isol' => 0xFE9D, 'fina' => 0xFE9E, 'init' => 0xFE9F, 'medi' => 0xFEA0],
        0x062D => ['isol' => 0xFEA1, 'fina' => 0xFEA2, 'init' => 0xFEA3, 'medi' => 0xFEA4],
        0x062E => ['isol' => 0xFEA5, 'fina' => 0xFEA6, 'init' => 0xFEA7, 'medi' => 0xFEA8],
        0x062F => ['isol' => 0xFEA9, 'fina' => 0xFEAA],
        0x0630 => ['isol' => 0xFEAB, 'fina' => 0xFEAC],
        0x0631 => ['isol' => 0xFEAD, 'fina' => 0xFEAE],
        0x0632 => ['isol' => 0xFEAF, 'fina' => 0xFEB0],
        0x0633 => ['isol' => 0xFEB1, 'fina' => 0xFEB2, 'init' => 0xFEB3, 'medi' => 0xFEB4],
        0x0634 => ['isol' => 0xFEB5, 'fina' => 0xFEB6, 'init' => 0xFEB7, 'medi' => 0xFEB8],
        0x0635 => ['isol' => 0xFEB9, 'fina' => 0xFEBA, 'init' => 0xFEBB, 'medi' => 0xFEBC],
        0x0636 => ['isol' => 0xFEBD, 'fina' => 0xFEBE, 'init' => 0xFEBF, 'medi' => 0xFEC0],
        0x0637 => ['isol' => 0xFEC1, 'fina' => 0xFEC2, 'init' => 0xFEC3, 'medi' => 0xFEC4],
        0x0638 => ['isol' => 0xFEC5, 'fina' => 0xFEC6, 'init' => 0xFEC7, 'medi' => 0xFEC8],
        0x0639 => ['isol' => 0xFEC9, 'fina' => 0xFECA, 'init' => 0xFECB, 'medi' => 0xFECC],
        0x063A => ['isol' => 0xFECD, 'fina' => 0xFECE, 'init' => 0xFECF, 'medi' => 0xFED0],
        0x0641 => ['isol' => 0xFED1, 'fina' => 0xFED2, 'init' => 0xFED3, 'medi' => 0xFED4],
        0x0642 => ['isol' => 0xFED5, 'fina' => 0xFED6, 'init' => 0xFED7, 'medi' => 0xFED8],
        0x0643 => ['isol' => 0xFED9, 'fina' => 0xFEDA, 'init' => 0xFEDB, 'medi' => 0xFEDC],
        0x0644 => ['isol' => 0xFEDD, 'fina' => 0xFEDE, 'init' => 0xFEDF, 'medi' => 0xFEE0],
        0x0645 => ['isol' => 0xFEE1, 'fina' => 0xFEE2, 'init' => 0xFEE3, 'medi' => 0xFEE4],
        0x0646 => ['isol' => 0xFEE5, 'fina' => 0xFEE6, 'init' => 0xFEE7, 'medi' => 0xFEE8],
        0x0647 => ['isol' => 0xFEE9, 'fina' => 0xFEEA, 'init' => 0xFEEB, 'medi' => 0xFEEC],
        0x0648 => ['isol' => 0xFEED, 'fina' => 0xFEEE],
        0x0649 => ['isol' => 0xFEEF, 'fina' => 0xFEF0],
        0x064A => ['isol' => 0xFEF1, 'fina' => 0xFEF2, 'init' => 0xFEF3, 'medi' => 0xFEF4],
    ];

    /**
     * @return list<int>
     */
    public static function codepoints(string $text): array
    {
        $points = [];
        $length = mb_strlen($text, 'UTF-8');
        for ($i = 0; $i < $length; $i++) {
            $points[] = mb_ord(mb_substr($text, $i, 1, 'UTF-8'), 'UTF-8');
        }

        return $points;
    }

    /**
     * @param  list<int>  $codepoints
     * @return array{gids: list<int>, codepoints: list<int>}
     */
    protected static function mapCodepoints(array $codepoints, TtfFont $font): array
    {
        $gids = [];
        $kept = [];
        foreach ($codepoints as $cp) {
            $gid = $font->glyphId($cp);
            if ($gid === 0 && $cp !== 32) {
                continue;
            }
            $gids[] = $gid;
            $kept[] = $cp;
        }

        return ['gids' => $gids, 'codepoints' => $kept];
    }

    /**
     * True when a left matra or two-part vowel is still a raw cmap glyph (not ligated).
     *
     * @param  list<int>  $gids
     * @param  list<int>  $codepoints
     */
    protected static function stillHasRawLeftMatra(array $gids, array $codepoints, TtfFont $font): bool
    {
        foreach ($codepoints as $cp) {
            if (! isset(self::LEFT_MATRAS[$cp]) && $cp !== 0x09CB && $cp !== 0x09CC) {
                continue;
            }
            $gid = $font->glyphId($cp);
            if ($gid > 0 && in_array($gid, $gids, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<int>  $codepoints
     */
    protected static function hasDevanagariLeftMatra(array $codepoints): bool
    {
        foreach ($codepoints as $cp) {
            if ($cp === 0x093F || $cp === 0x094E) {
                return true;
            }
        }

        return false;
    }

    protected static function isLeftMatraGlyph(int $gid, TtfFont $font): bool
    {
        foreach (array_keys(self::LEFT_MATRAS) as $cp) {
            if ($gid > 0 && $gid === $font->glyphId((int) $cp)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Noto type-1 lists remap isolated matras to contextual alts (ि→ॅ, ी→wide PUA).
     * Those alts are only valid from a type 5/6 match; applying the list itself is wrong.
     *
     * @var array<string, array<int, true>>
     */
    protected static array $matraGlyphs = [];

    protected static function isMatraGlyph(int $gid, TtfFont $font): bool
    {
        if ($gid <= 0) {
            return false;
        }
        if (self::isLeftMatraGlyph($gid, $font)) {
            return true;
        }

        $key = $font->path;
        if (! isset(self::$matraGlyphs[$key])) {
            $marks = [];
            foreach ($font->cmap() as $cp => $mapped) {
                if ($mapped > 0 && self::indicCategory((int) $cp) === 'M') {
                    $marks[$mapped] = true;
                }
            }
            self::$matraGlyphs[$key] = $marks;
        }

        return isset(self::$matraGlyphs[$key][$gid]);
    }

    /**
     * Shape a run into glyph ids using cmap + optional GSUB / joining.
     *
     * @return array{gids: list<int>, codepoints: list<int>, positions: list<array{gid: int, x: float, y: float, w: float}>}
     */
    public static function shape(string $text, TtfFont $font, bool $rtl = false): array
    {
        $raw = self::codepoints($text);
        $logical = self::mapCodepoints($raw, $font);
        $reordered = self::mapCodepoints(self::reorderIndic($raw), $font);

        $logicalGids = self::applyArabicFeatures($logical['gids'], $logical['codepoints'], $font);
        $logicalGids = self::applyLookups($logicalGids, $font, ['ccmp', 'rlig', 'liga', 'calt', 'dlig']);
        $logicalGids = self::applyIndicFeatures($logicalGids, $font, false);

        $gids = $logicalGids;
        $keptCodepoints = $logical['codepoints'];

        $needsReorder = self::stillHasRawLeftMatra($logicalGids, $raw, $font)
            || self::hasDevanagariLeftMatra($raw);

        if ($needsReorder) {
            $reorderedGids = self::applyArabicFeatures($reordered['gids'], $reordered['codepoints'], $font);
            $reorderedGids = self::applyLookups($reorderedGids, $font, ['ccmp', 'rlig', 'liga', 'calt', 'dlig']);
            $reorderedGids = self::applyIndicFeatures($reorderedGids, $font, true);
            $gids = $reorderedGids;
            $keptCodepoints = $reordered['codepoints'];
        }

        $gids = self::applyPresentationForms($gids, $keptCodepoints, $font);

        $positions = self::positionGlyphs($gids, $font);

        if ($rtl && self::isRtlText($text)) {
            $gids = array_reverse($gids);
            $keptCodepoints = array_reverse($keptCodepoints);
            $positions = self::reversePositions($positions);
        }

        return ['gids' => $gids, 'codepoints' => $keptCodepoints, 'positions' => $positions];
    }

    /**
     * @param  list<int>  $codepoints
     * @return list<int>
     */
    public static function reorderIndic(array $codepoints): array
    {
        $codepoints = self::splitTwoPartMatras($codepoints);
        $output = [];

        foreach (self::indicSyllables($codepoints) as [$start, $end]) {
            $syllable = array_slice($codepoints, $start, $end - $start);
            $left = [];
            $rest = [];
            foreach ($syllable as $cp) {
                if (isset(self::LEFT_MATRAS[$cp])) {
                    $left[] = $cp;
                } else {
                    $rest[] = $cp;
                }
            }
            array_push($output, ...$left, ...$rest);
        }

        return $output;
    }

    /**
     * Bengali ো / ৌ are one codepoint but two visual halves.
     *
     * @param  list<int>  $codepoints
     * @return list<int>
     */
    protected static function splitTwoPartMatras(array $codepoints): array
    {
        $output = [];
        foreach ($codepoints as $cp) {
            if ($cp === 0x09CB) {
                $output[] = 0x09C7;
                $output[] = 0x09BE;
            } elseif ($cp === 0x09CC) {
                $output[] = 0x09C7;
                $output[] = 0x09D7;
            } elseif ($cp === 0x0BCA) {
                $output[] = 0x0BC6;
                $output[] = 0x0BBE;
            } elseif ($cp === 0x0BCB) {
                $output[] = 0x0BC7;
                $output[] = 0x0BBE;
            } elseif ($cp === 0x0BCC) {
                $output[] = 0x0BC6;
                $output[] = 0x0BD7;
            } else {
                $output[] = $cp;
            }
        }

        return $output;
    }

    /**
     * @param  list<int>  $codepoints
     * @return list<array{0: int, 1: int}>
     */
    protected static function indicSyllables(array $codepoints): array
    {
        $syllables = [];
        $i = 0;
        $length = count($codepoints);

        while ($i < $length) {
            $start = $i;
            $cat = self::indicCategory($codepoints[$i]);

            if ($cat === 'V') {
                $i++;
                if ($i < $length && self::indicCategory($codepoints[$i]) === 'N') {
                    $i++;
                }
                while ($i < $length && self::indicCategory($codepoints[$i]) === 'M') {
                    $i++;
                }
                while ($i < $length && self::indicCategory($codepoints[$i]) === 'X') {
                    $i++;
                }
            } elseif ($cat === 'C') {
                while ($i < $length && self::indicCategory($codepoints[$i]) === 'C') {
                    $i++;
                    if ($i < $length && self::indicCategory($codepoints[$i]) === 'N') {
                        $i++;
                    }
                    if ($i < $length && self::indicCategory($codepoints[$i]) === 'H') {
                        $i++;
                        if ($i < $length && in_array(self::indicCategory($codepoints[$i]), ['C', 'Z'], true)) {
                            if (self::indicCategory($codepoints[$i]) === 'Z') {
                                $i++;
                            }

                            continue;
                        }
                    }

                    break;
                }
                while ($i < $length && self::indicCategory($codepoints[$i]) === 'M') {
                    $i++;
                }
                while ($i < $length && self::indicCategory($codepoints[$i]) === 'X') {
                    $i++;
                }
            } else {
                $i++;
            }

            $syllables[] = [$start, max($i, $start + 1)];
        }

        return $syllables;
    }

    protected static function indicCategory(int $codepoint): string
    {
        if (in_array($codepoint, [0x094D, 0x09CD, 0x0A4D, 0x0ACD, 0x0B4D, 0x0BCD, 0x0C4D, 0x0CCD, 0x0D4D, 0x0DCA], true)) {
            return 'H';
        }
        if (in_array($codepoint, [0x093C, 0x09BC, 0x0A3C, 0x0ABC, 0x0B3C], true)) {
            return 'N';
        }
        if (isset(self::LEFT_MATRAS[$codepoint]) || in_array($codepoint, [0x09CB, 0x09CC, 0x0BCA, 0x0BCB, 0x0BCC], true)) {
            return 'M';
        }
        if (
            ($codepoint >= 0x093A && $codepoint <= 0x094F)
            || ($codepoint >= 0x0962 && $codepoint <= 0x0963)
            || ($codepoint >= 0x09BE && $codepoint <= 0x09CC)
            || $codepoint === 0x09D7
            || ($codepoint >= 0x09E2 && $codepoint <= 0x09E3)
            || ($codepoint >= 0x0A3E && $codepoint <= 0x0A4C)
            || ($codepoint >= 0x0ABE && $codepoint <= 0x0ACC)
            || ($codepoint >= 0x0B3E && $codepoint <= 0x0B4C)
            || ($codepoint >= 0x0BBE && $codepoint <= 0x0BCC)
            || $codepoint === 0x0BD7
            || ($codepoint >= 0x0C3E && $codepoint <= 0x0C4C)
            || ($codepoint >= 0x0CBE && $codepoint <= 0x0CCC)
            || ($codepoint >= 0x0D3E && $codepoint <= 0x0D4C)
            || ($codepoint >= 0x0DCF && $codepoint <= 0x0DDF)
        ) {
            return 'M';
        }
        if (
            ($codepoint >= 0x0900 && $codepoint <= 0x0903)
            || ($codepoint >= 0x0951 && $codepoint <= 0x0957)
            || ($codepoint >= 0x0981 && $codepoint <= 0x0983)
            || ($codepoint >= 0x0A01 && $codepoint <= 0x0A03)
            || ($codepoint >= 0x0A81 && $codepoint <= 0x0A83)
            || ($codepoint >= 0x0B01 && $codepoint <= 0x0B03)
            || ($codepoint >= 0x0B82 && $codepoint <= 0x0B83)
            || ($codepoint >= 0x0C01 && $codepoint <= 0x0C03)
            || ($codepoint >= 0x0C81 && $codepoint <= 0x0C83)
            || ($codepoint >= 0x0D01 && $codepoint <= 0x0D03)
            || ($codepoint >= 0x0D81 && $codepoint <= 0x0D83)
        ) {
            return 'X';
        }
        if (
            ($codepoint >= 0x0904 && $codepoint <= 0x0914)
            || ($codepoint >= 0x0985 && $codepoint <= 0x0994)
            || ($codepoint >= 0x0A05 && $codepoint <= 0x0A14)
            || ($codepoint >= 0x0A85 && $codepoint <= 0x0A94)
            || ($codepoint >= 0x0B05 && $codepoint <= 0x0B14)
            || ($codepoint >= 0x0B85 && $codepoint <= 0x0B94)
            || ($codepoint >= 0x0C05 && $codepoint <= 0x0C14)
            || ($codepoint >= 0x0C85 && $codepoint <= 0x0C94)
            || ($codepoint >= 0x0D05 && $codepoint <= 0x0D14)
            || ($codepoint >= 0x0D85 && $codepoint <= 0x0D96)
        ) {
            return 'V';
        }
        if (
            ($codepoint >= 0x0915 && $codepoint <= 0x0939)
            || ($codepoint >= 0x0958 && $codepoint <= 0x095F)
            || ($codepoint >= 0x0995 && $codepoint <= 0x09B9)
            || in_array($codepoint, [0x09CE, 0x09DC, 0x09DD, 0x09DF, 0x09F0, 0x09F1], true)
            || ($codepoint >= 0x0A15 && $codepoint <= 0x0A39)
            || ($codepoint >= 0x0A95 && $codepoint <= 0x0AB9)
            || ($codepoint >= 0x0B15 && $codepoint <= 0x0B39)
            || ($codepoint >= 0x0B95 && $codepoint <= 0x0BB9)
            || ($codepoint >= 0x0C15 && $codepoint <= 0x0C39)
            || ($codepoint >= 0x0C95 && $codepoint <= 0x0CB9)
            || ($codepoint >= 0x0D15 && $codepoint <= 0x0D39)
            || ($codepoint >= 0x0D9A && $codepoint <= 0x0DC6)
        ) {
            return 'C';
        }
        if ($codepoint === 0x200C || $codepoint === 0x200D) {
            return 'Z';
        }

        return 'O';
    }

    /**
     * @param  list<int>  $gids
     * @param  list<int>  $codepoints
     * @return list<int>
     */
    protected static function applyArabicFeatures(array $gids, array $codepoints, TtfFont $font): array
    {
        $hasArabic = false;
        foreach ($codepoints as $cp) {
            if (preg_match(self::ARABIC_LETTER, mb_chr($cp, 'UTF-8'))) {
                $hasArabic = true;
                break;
            }
        }

        if (! $hasArabic) {
            return $gids;
        }

        $forms = self::joiningForms($codepoints);
        $shaped = $gids;

        foreach ($forms as $index => $form) {
            $shaped = self::applyLookupsAt($shaped, $index, $font, [$form]);
        }

        return $shaped;
    }

    /**
     * Apply OpenType GSUB features in the standard Indic processing order.
     *
     * @param  list<int>  $gids
     * @return list<int>
     */
    protected static function applyIndicFeatures(array $gids, TtfFont $font, bool $reordered = false): array
    {
        $gids = self::applyLookups($gids, $font, ['pstf', 'blwf', 'rphf', 'rkrf', 'vatu', 'akhn'], [4]);
        $gids = self::applyLookups($gids, $font, ['locl', 'nukt', 'akhn', 'cjct'], [1, 4]);
        $gids = self::applyContextualHalf($gids, $font);
        $gids = self::applyLookups($gids, $font, ['rphf', 'rkrf', 'pref', 'blwf', 'abvf', 'pstf', 'vatu', 'cjct'], [1, 2, 4, 5, 6]);
        $gids = self::applyLookups($gids, $font, ['pres', 'abvs', 'blws', 'psts', 'haln'], [1, 2, 4, 5, 6], $reordered);
        $gids = self::applyLookups($gids, $font, ['pres', 'abvs', 'blws', 'psts', 'haln', 'cjct'], [1, 2, 4, 5, 6], $reordered);

        return $gids;
    }

    /**
     * Apply the `half` feature only to a consonant that is followed by virama + another glyph.
     *
     * @param  list<int>  $gids
     * @return list<int>
     */
    protected static function applyContextualHalf(array $gids, TtfFont $font): array
    {
        $substitutes = [];
        $ligatures = [];
        foreach ($font->lookupsForFeatures(['half']) as $lookup) {
            $type = $lookup['type'] ?? 0;
            if ($type === 1) {
                $substitutes += $lookup['substitutes'] ?? [];
            } elseif ($type === 4) {
                foreach ($lookup['ligatures'] ?? [] as $first => $candidates) {
                    $ligatures[$first] = array_merge($ligatures[$first] ?? [], $candidates);
                }
            }
        }

        $virama = [];
        foreach ([0x094D, 0x09CD, 0x0A4D, 0x0ACD, 0x0BCD, 0x0C4D, 0x0CCD, 0x0D4D] as $cp) {
            $gid = $font->glyphId($cp);
            if ($gid > 0) {
                $virama[$gid] = true;
            }
        }

        $i = 0;
        while ($i < count($gids) - 1) {
            if (! isset($virama[$gids[$i + 1]]) || ! isset($gids[$i + 2])) {
                $i++;

                continue;
            }

            $half = $substitutes[$gids[$i]] ?? null;
            if ($half !== null && $half > 0) {
                $gids[$i] = $half;
                array_splice($gids, $i + 1, 1);
                $i++;

                continue;
            }

            $matched = false;
            $candidates = $ligatures[$gids[$i]] ?? [];
            usort($candidates, fn (array $a, array $b): int => count($b['components'] ?? []) <=> count($a['components'] ?? []));
            foreach ($candidates as $ligature) {
                $components = $ligature['components'] ?? [];
                $need = count($components);
                if ($need === 0) {
                    continue;
                }
                $slice = array_slice($gids, $i + 1, $need);
                if ($slice === $components) {
                    array_splice($gids, $i, $need + 1, [$ligature['glyph']]);
                    $matched = true;
                    break;
                }
            }

            $i += $matched ? 0 : 1;
            if ($matched) {
                $i++;
            }
        }

        return $gids;
    }

    /**
     * @param  list<int>  $codepoints
     * @return array<int, string>
     */
    protected static function joiningForms(array $codepoints): array
    {
        $types = [];
        foreach ($codepoints as $cp) {
            $char = mb_chr($cp, 'UTF-8');
            if (preg_match(self::TRANSPARENT, $char)) {
                $types[] = 'T';
            } elseif (! preg_match(self::ARABIC_LETTER, $char) || $cp === 0x0621) {
                $types[] = 'U';
            } elseif (isset(self::RIGHT_JOINING[$cp])) {
                $types[] = 'R';
            } else {
                $types[] = 'D';
            }
        }

        $forms = [];
        $count = count($types);
        for ($i = 0; $i < $count; $i++) {
            if ($types[$i] === 'T' || $types[$i] === 'U') {
                $forms[$i] = 'isol';

                continue;
            }

            $prev = 'U';
            for ($j = $i - 1; $j >= 0; $j--) {
                if ($types[$j] !== 'T') {
                    $prev = $types[$j];
                    break;
                }
            }
            $next = 'U';
            for ($j = $i + 1; $j < $count; $j++) {
                if ($types[$j] !== 'T') {
                    $next = $types[$j];
                    break;
                }
            }

            $joinsLeft = ($prev === 'D');
            $joinsRight = ($types[$i] === 'D' && ($next === 'D' || $next === 'R'));

            $forms[$i] = match (true) {
                $joinsLeft && $joinsRight => 'medi',
                $joinsLeft => 'fina',
                $joinsRight => 'init',
                default => 'isol',
            };
        }

        return $forms;
    }

    /**
     * @param  list<int>  $gids
     * @param  list<int>  $codepoints
     * @return list<int>
     */
    protected static function applyPresentationForms(array $gids, array $codepoints, TtfFont $font): array
    {
        $forms = self::joiningForms($codepoints);

        foreach ($codepoints as $index => $cp) {
            $table = self::PRESENTATION[$cp] ?? null;
            if ($table === null || ! isset($gids[$index])) {
                continue;
            }

            $form = $forms[$index] ?? 'isol';
            $mapped = $table[$form] ?? $table['isol'];
            $original = $font->glyphId($cp);
            if ($gids[$index] !== $original) {
                continue;
            }

            if ($font->hasGlyph($mapped)) {
                $gids[$index] = $font->glyphId($mapped);
            }
        }

        return $gids;
    }

    /**
     * @param  list<int>  $gids
     * @param  list<string>  $features
     * @param  list<int>  $types
     * @return list<int>
     */
    public static function applyLookups(array $gids, TtfFont $font, array $features, array $types = [1, 4, 6], bool $reordered = false): array
    {
        foreach ($font->lookupsForFeatures($features) as $lookup) {
            $type = $lookup['type'] ?? 0;
            if (! in_array($type, $types, true)) {
                continue;
            }

            if ($type === 4) {
                $ligatures = $lookup['ligatures'] ?? [];
                if ($reordered) {
                    $ligatures = self::keepReorderedLeftMatraLigatures($ligatures, $font);
                }
                $gids = self::applyLigatures($gids, $ligatures, $reordered ? $font : null);
            } elseif ($type === 2) {
                $gids = self::applyMultiple($gids, $lookup['sequences'] ?? []);
            } elseif ($type === 1) {
                $substitutes = $lookup['substitutes'] ?? [];
                foreach ($gids as $i => $gid) {
                    if (self::isMatraGlyph($gid, $font)) {
                        continue;
                    }
                    $replacement = $substitutes[$gid] ?? null;
                    if ($replacement !== null && $replacement > 0) {
                        $gids[$i] = $replacement;
                    }
                }
            } elseif ($type === 5 || $type === 6) {
                $gids = self::applyContextLookup($gids, $lookup, $font);
            }
        }

        return $gids;
    }

    /**
     * @param  list<int>  $gids
     * @param  array<string, mixed>  $lookup
     * @return list<int>
     */
    /**
     * @param  list<int>  $gids
     * @param  array<string, mixed>  $lookup
     * @return list<int>
     */
    protected static function applyContextLookup(array $gids, array $lookup, TtfFont $font, int $depth = 0): array
    {
        $format = (int) ($lookup['format'] ?? 3);
        if ($format === 2 && isset($lookup['classSets'])) {
            return self::applyClassChain($gids, $lookup, $font, $depth);
        }
        if ($format === 1 && isset($lookup['rules'])) {
            return self::applyGlyphRules($gids, $lookup, $font, $depth);
        }

        return self::applyChain($gids, $lookup, $font, $depth);
    }

    /**
     * @param  list<int>  $gids
     * @param  array<string, mixed>  $lookup
     * @return list<int>
     */
    protected static function applyGlyphRules(array $gids, array $lookup, TtfFont $font, int $depth = 0): array
    {
        if ($depth > 8) {
            return $gids;
        }

        $ignoreMarks = (((int) ($lookup['flag'] ?? 0)) & 0x0008) !== 0;
        $i = 0;
        while ($i < count($gids)) {
            foreach ($lookup['rules'][$gids[$i]] ?? [] as $rule) {
                $need = 1 + count($rule['input'] ?? []);
                $input = self::collectSequence($gids, $i, 1, $need, $ignoreMarks, $font);
                if ($input === null) {
                    continue;
                }
                $matched = true;
                foreach ($rule['input'] as $n => $gid) {
                    if ($gids[$input[$n + 1]] !== $gid) {
                        $matched = false;
                        break;
                    }
                }
                if ($matched && ($rule['backtrack'] ?? []) !== []) {
                    $back = self::collectSequence($gids, $input[0] - 1, -1, count($rule['backtrack']), $ignoreMarks, $font);
                    if ($back === null) {
                        $matched = false;
                    } else {
                        foreach ($rule['backtrack'] as $n => $gid) {
                            if ($gids[$back[$n]] !== $gid) {
                                $matched = false;
                                break;
                            }
                        }
                    }
                }
                if ($matched && ($rule['lookahead'] ?? []) !== []) {
                    $last = $input[array_key_last($input)];
                    $ahead = self::collectSequence($gids, $last + 1, 1, count($rule['lookahead']), $ignoreMarks, $font);
                    if ($ahead === null) {
                        $matched = false;
                    } else {
                        foreach ($rule['lookahead'] as $n => $gid) {
                            if ($gids[$ahead[$n]] !== $gid) {
                                $matched = false;
                                break;
                            }
                        }
                    }
                }
                if (! $matched) {
                    continue;
                }

                foreach ($rule['substs'] as $subst) {
                    $seq = (int) $subst['seq'];
                    if (! isset($input[$seq])) {
                        continue;
                    }
                    $gids = self::applyNestedLookup($gids, $input[$seq], (int) $subst['lookup'], $font, $depth + 1);
                    $input = self::collectSequence($gids, $i, 1, $need, $ignoreMarks, $font) ?? $input;
                }
                break;
            }

            $i++;
        }

        return $gids;
    }

    /**
     * @param  list<int>  $gids
     * @param  array<string, mixed>  $lookup
     * @return list<int>
     */
    protected static function applyClassChain(array $gids, array $lookup, TtfFont $font, int $depth = 0): array
    {
        if ($depth > 8) {
            return $gids;
        }

        $ignoreMarks = (((int) ($lookup['flag'] ?? 0)) & 0x0008) !== 0;
        $coverage = $lookup['coverage'] ?? [];
        $inputClass = $lookup['inputClass'] ?? [];
        $backtrackClass = $lookup['backtrackClass'] ?? [];
        $lookaheadClass = $lookup['lookaheadClass'] ?? [];
        $i = 0;
        while ($i < count($gids)) {
            if (! isset($coverage[$gids[$i]])) {
                $i++;

                continue;
            }

            $class0 = $inputClass[$gids[$i]] ?? 0;
            foreach ($lookup['classSets'][$class0] ?? [] as $rule) {
                $need = 1 + count($rule['input'] ?? []);
                $input = self::collectSequence($gids, $i, 1, $need, $ignoreMarks, $font);
                if ($input === null) {
                    continue;
                }
                $matched = true;
                foreach ($rule['input'] as $n => $class) {
                    if (($inputClass[$gids[$input[$n + 1]]] ?? 0) !== $class) {
                        $matched = false;
                        break;
                    }
                }
                if ($matched && ($rule['backtrack'] ?? []) !== []) {
                    $back = self::collectSequence($gids, $input[0] - 1, -1, count($rule['backtrack']), $ignoreMarks, $font);
                    if ($back === null) {
                        $matched = false;
                    } else {
                        foreach ($rule['backtrack'] as $n => $class) {
                            if (($backtrackClass[$gids[$back[$n]]] ?? 0) !== $class) {
                                $matched = false;
                                break;
                            }
                        }
                    }
                }
                if ($matched && ($rule['lookahead'] ?? []) !== []) {
                    $last = $input[array_key_last($input)];
                    $ahead = self::collectSequence($gids, $last + 1, 1, count($rule['lookahead']), $ignoreMarks, $font);
                    if ($ahead === null) {
                        $matched = false;
                    } else {
                        foreach ($rule['lookahead'] as $n => $class) {
                            if (($lookaheadClass[$gids[$ahead[$n]]] ?? 0) !== $class) {
                                $matched = false;
                                break;
                            }
                        }
                    }
                }
                if (! $matched) {
                    continue;
                }

                $before = $gids;
                foreach ($rule['substs'] as $subst) {
                    $seq = (int) $subst['seq'];
                    if (! isset($input[$seq])) {
                        continue;
                    }
                    $gids = self::applyNestedLookup($gids, $input[$seq], (int) $subst['lookup'], $font, $depth + 1);
                    $input = self::collectSequence($gids, $i, 1, $need, $ignoreMarks, $font) ?? $input;
                }
                break;
            }

            $i++;
        }

        return $gids;
    }

    protected static function applyChain(array $gids, array $lookup, TtfFont $font, int $depth = 0): array
    {
        if ($depth > 8) {
            return $gids;
        }

        $ignoreMarks = (((int) ($lookup['flag'] ?? 0)) & 0x0008) !== 0;
        $i = 0;
        while ($i < count($gids)) {
            $input = self::collectSequence($gids, $i, 1, count($lookup['input'] ?? []), $ignoreMarks, $font);
            if ($input === null) {
                $i++;

                continue;
            }

            $matched = true;
            foreach ($lookup['input'] as $n => $coverage) {
                if (! isset($coverage[$gids[$input[$n]]])) {
                    $matched = false;
                    break;
                }
            }

            if ($matched && ($lookup['backtrack'] ?? []) !== []) {
                $back = self::collectSequence($gids, $input[0] - 1, -1, count($lookup['backtrack']), $ignoreMarks, $font);
                if ($back === null) {
                    $matched = false;
                } else {
                    foreach ($lookup['backtrack'] as $n => $coverage) {
                        if (! isset($coverage[$gids[$back[$n]]])) {
                            $matched = false;
                            break;
                        }
                    }
                }
            }

            if ($matched && ($lookup['lookahead'] ?? []) !== []) {
                $lastInput = $input[array_key_last($input)];
                $ahead = self::collectSequence($gids, $lastInput + 1, 1, count($lookup['lookahead']), $ignoreMarks, $font);
                if ($ahead === null) {
                    $matched = false;
                } else {
                    foreach ($lookup['lookahead'] as $n => $coverage) {
                        if (! isset($coverage[$gids[$ahead[$n]]])) {
                            $matched = false;
                            break;
                        }
                    }
                }
            }

            if (! $matched) {
                $i++;

                continue;
            }

            $before = $gids;
            foreach ($lookup['substs'] ?? [] as $subst) {
                $seq = (int) $subst['seq'];
                if (! isset($input[$seq])) {
                    continue;
                }
                $gids = self::applyNestedLookup($gids, $input[$seq], (int) $subst['lookup'], $font, $depth + 1);
                $input = self::collectSequence($gids, $i, 1, count($lookup['input'] ?? []), $ignoreMarks, $font) ?? $input;
            }

            $i += $gids === $before ? 1 : 0;
            if ($gids !== $before) {
                continue;
            }
            $i++;
        }

        return $gids;
    }

    /**
     * @param  list<int>  $gids
     * @return list<int>|null
     */
    protected static function collectSequence(array $gids, int $start, int $dir, int $need, bool $ignoreMarks, TtfFont $font): ?array
    {
        if ($need === 0) {
            return [];
        }

        $indexes = [];
        $pos = $start;
        while ($pos >= 0 && $pos < count($gids) && count($indexes) < $need) {
            if ($ignoreMarks && $font->isMarkGlyph($gids[$pos])) {
                $pos += $dir;

                continue;
            }
            $indexes[] = $pos;
            $pos += $dir;
        }

        return count($indexes) === $need ? $indexes : null;
    }

    /**
     * @param  list<int>  $gids
     * @return list<int>
     */
    protected static function applyNestedLookup(array $gids, int $index, int $lookupIndex, TtfFont $font, int $depth): array
    {
        foreach ($font->lookupsAt($lookupIndex) as $lookup) {
            $type = $lookup['type'] ?? 0;
            if ($type === 1) {
                $substitutes = $lookup['substitutes'] ?? [];
                $current = $gids[$index] ?? -1;
                if (self::isMatraGlyph($current, $font)) {
                    continue;
                }
                $replacement = $substitutes[$current] ?? null;
                if ($replacement !== null && $replacement > 0) {
                    $gids[$index] = $replacement;
                }
            } elseif ($type === 2) {
                $sequence = $lookup['sequences'][$gids[$index] ?? -1] ?? null;
                if (is_array($sequence)) {
                    array_splice($gids, $index, 1, $sequence);
                }
            } elseif ($type === 4) {
                $slice = array_slice($gids, $index);
                $replaced = self::applyLigatures($slice, $lookup['ligatures'] ?? []);
                if ($replaced !== $slice) {
                    array_splice($gids, $index, count($slice), $replaced);
                }
            } elseif ($type === 5 || $type === 6) {
                $gids = self::applyContextLookup($gids, $lookup, $font, $depth);
            }
        }

        return $gids;
    }

    /**
     * @param  list<int>  $gids
     * @return list<array{gid: int, x: float, y: float, w: float}>
     */
    protected static function positionGlyphs(array $gids, TtfFont $font): array
    {
        $positions = [];
        $cursor = 0.0;
        $baseIndex = null;

        foreach ($gids as $gid) {
            $advance = $font->advance($gid, 1.0);
            $attach = ($baseIndex !== null) ? $font->markAttachment($gids[$baseIndex], $gid) : null;

            if ($attach !== null || ($baseIndex !== null && $font->isMarkGlyph($gid))) {
                $base = $positions[$baseIndex];
                $dx = is_array($attach) ? $attach[0] : 0;
                $dy = is_array($attach) ? $attach[1] : 0;
                $positions[] = [
                    'gid' => $gid,
                    'x' => $base['x'] + ($dx / $font->unitsPerEm),
                    'y' => $dy / $font->unitsPerEm,
                    'w' => 0.0,
                ];

                continue;
            }

            $positions[] = [
                'gid' => $gid,
                'x' => $cursor,
                'y' => 0.0,
                'w' => $advance,
            ];
            $baseIndex = count($positions) - 1;
            $cursor += $advance;
        }

        return self::applyGpos($gids, $positions, $font);
    }

    /**
     * @param  list<int>  $gids
     * @param  list<array{gid: int, x: float, y: float, w: float}>  $positions
     * @return list<array{gid: int, x: float, y: float, w: float}>
     */
    protected static function applyGpos(array $gids, array $positions, TtfFont $font): array
    {
        foreach ($font->gposLookupsForFeatures(['dist', 'kern', 'abvm', 'blwm']) as $lookup) {
            $type = $lookup['type'] ?? 0;
            if ($type === 1) {
                foreach ($gids as $i => $gid) {
                    if (isset($lookup['values'][$gid])) {
                        $positions = self::applyValue($positions, $i, $lookup['values'][$gid], $font);
                    }
                }
            } elseif ($type === 2) {
                for ($i = 0; $i < count($gids) - 1; $i++) {
                    $pair = $lookup['pairs'][$gids[$i]][$gids[$i + 1]] ?? null;
                    if (! is_array($pair)) {
                        continue;
                    }
                    $positions = self::applyValue($positions, $i, $pair[0], $font);
                    $positions = self::applyValue($positions, $i + 1, $pair[1], $font);
                }
            } elseif ($type === 8) {
                $positions = self::applyGposChain($gids, $positions, $lookup, $font);
            }
        }

        return $positions;
    }

    /**
     * @param  list<int>  $gids
     * @param  list<array{gid: int, x: float, y: float, w: float}>  $positions
     * @param  array<string, mixed>  $lookup
     * @return list<array{gid: int, x: float, y: float, w: float}>
     */
    protected static function applyGposChain(array $gids, array $positions, array $lookup, TtfFont $font): array
    {
        $format = (int) ($lookup['format'] ?? 3);
        $ignoreMarks = (((int) ($lookup['flag'] ?? 0)) & 0x0008) !== 0;
        $i = 0;
        while ($i < count($gids)) {
            $input = null;
            $substs = [];

            if ($format === 2 && isset($lookup['classSets'])) {
                if (! isset($lookup['coverage'][$gids[$i]])) {
                    $i++;

                    continue;
                }
                $class0 = $lookup['inputClass'][$gids[$i]] ?? 0;
                foreach ($lookup['classSets'][$class0] ?? [] as $rule) {
                    $need = 1 + count($rule['input'] ?? []);
                    $seq = self::collectSequence($gids, $i, 1, $need, $ignoreMarks, $font);
                    if ($seq === null) {
                        continue;
                    }
                    $matched = true;
                    foreach ($rule['input'] as $n => $class) {
                        if (($lookup['inputClass'][$gids[$seq[$n + 1]]] ?? 0) !== $class) {
                            $matched = false;
                            break;
                        }
                    }
                    if ($matched && ($rule['backtrack'] ?? []) !== []) {
                        $back = self::collectSequence($gids, $seq[0] - 1, -1, count($rule['backtrack']), $ignoreMarks, $font);
                        if ($back === null) {
                            $matched = false;
                        } else {
                            foreach ($rule['backtrack'] as $n => $class) {
                                if (($lookup['backtrackClass'][$gids[$back[$n]]] ?? 0) !== $class) {
                                    $matched = false;
                                    break;
                                }
                            }
                        }
                    }
                    if ($matched && ($rule['lookahead'] ?? []) !== []) {
                        $last = $seq[array_key_last($seq)];
                        $ahead = self::collectSequence($gids, $last + 1, 1, count($rule['lookahead']), $ignoreMarks, $font);
                        if ($ahead === null) {
                            $matched = false;
                        } else {
                            foreach ($rule['lookahead'] as $n => $class) {
                                if (($lookup['lookaheadClass'][$gids[$ahead[$n]]] ?? 0) !== $class) {
                                    $matched = false;
                                    break;
                                }
                            }
                        }
                    }
                    if ($matched) {
                        $input = $seq;
                        $substs = $rule['substs'] ?? [];
                        break;
                    }
                }
            } elseif (isset($lookup['input'])) {
                $input = self::collectSequence($gids, $i, 1, count($lookup['input']), $ignoreMarks, $font);
                $matched = $input !== null;
                if ($matched) {
                    foreach ($lookup['input'] as $n => $coverage) {
                        if (! isset($coverage[$gids[$input[$n]]])) {
                            $matched = false;
                            break;
                        }
                    }
                }
                if ($matched && ($lookup['backtrack'] ?? []) !== []) {
                    $back = self::collectSequence($gids, $input[0] - 1, -1, count($lookup['backtrack']), $ignoreMarks, $font);
                    $matched = $back !== null;
                    if ($matched) {
                        foreach ($lookup['backtrack'] as $n => $coverage) {
                            if (! isset($coverage[$gids[$back[$n]]])) {
                                $matched = false;
                                break;
                            }
                        }
                    }
                }
                if ($matched && ($lookup['lookahead'] ?? []) !== []) {
                    $last = $input[array_key_last($input)];
                    $ahead = self::collectSequence($gids, $last + 1, 1, count($lookup['lookahead']), $ignoreMarks, $font);
                    $matched = $ahead !== null;
                    if ($matched) {
                        foreach ($lookup['lookahead'] as $n => $coverage) {
                            if (! isset($coverage[$gids[$ahead[$n]]])) {
                                $matched = false;
                                break;
                            }
                        }
                    }
                }
                if ($matched) {
                    $substs = $lookup['substs'] ?? [];
                } else {
                    $input = null;
                }
            }

            if ($input !== null) {
                foreach ($substs as $subst) {
                    $seq = (int) $subst['seq'];
                    if (! isset($input[$seq])) {
                        continue;
                    }
                    $positions = self::applyNestedGpos($gids, $positions, $input[$seq], (int) $subst['lookup'], $font);
                }
            }

            $i++;
        }

        return $positions;
    }

    /**
     * @param  list<int>  $gids
     * @param  list<array{gid: int, x: float, y: float, w: float}>  $positions
     * @return list<array{gid: int, x: float, y: float, w: float}>
     */
    protected static function applyNestedGpos(array $gids, array $positions, int $index, int $lookupIndex, TtfFont $font): array
    {
        foreach ($font->gposLookupsAt($lookupIndex) as $lookup) {
            $type = $lookup['type'] ?? 0;
            if ($type === 1 && isset($lookup['values'][$gids[$index]])) {
                $positions = self::applyValue($positions, $index, $lookup['values'][$gids[$index]], $font);
            } elseif ($type === 2 && isset($gids[$index + 1])) {
                $pair = $lookup['pairs'][$gids[$index]][$gids[$index + 1]] ?? null;
                if (is_array($pair)) {
                    $positions = self::applyValue($positions, $index, $pair[0], $font);
                    $positions = self::applyValue($positions, $index + 1, $pair[1], $font);
                }
            }
        }

        return $positions;
    }

    /**
     * @param  list<array{gid: int, x: float, y: float, w: float}>  $positions
     * @param  array{0: int, 1: int, 2: int, 3: int}  $value
     * @return list<array{gid: int, x: float, y: float, w: float}>
     */
    protected static function applyValue(array $positions, int $index, array $value, TtfFont $font): array
    {
        if (! isset($positions[$index])) {
            return $positions;
        }

        $upem = max(1, $font->unitsPerEm);
        $positions[$index]['x'] += $value[0] / $upem;
        $positions[$index]['y'] += $value[1] / $upem;
        $positions[$index]['w'] += $value[2] / $upem;
        if ($value[2] !== 0) {
            $delta = $value[2] / $upem;
            for ($k = $index + 1; $k < count($positions); $k++) {
                $positions[$k]['x'] += $delta;
            }
        }

        return $positions;
    }

    /**
     * @param  list<array{gid: int, x: float, y: float, w: float}>  $positions
     * @return list<array{gid: int, x: float, y: float, w: float}>
     */
    protected static function reversePositions(array $positions): array
    {
        if ($positions === []) {
            return [];
        }

        $width = 0.0;
        foreach ($positions as $pos) {
            $width = max($width, $pos['x'] + $pos['w']);
        }

        $reversed = [];
        foreach (array_reverse($positions) as $pos) {
            $reversed[] = [
                'gid' => $pos['gid'],
                'x' => $width - $pos['x'] - $pos['w'],
                'y' => $pos['y'],
                'w' => $pos['w'],
            ];
        }

        return $reversed;
    }

    /**
     * @param  list<int>  $gids
     * @param  list<string>  $features
     * @return list<int>
     */
    protected static function applyLookupsAt(array $gids, int $index, TtfFont $font, array $features): array
    {
        if (! isset($gids[$index])) {
            return $gids;
        }

        foreach ($font->lookupsForFeatures($features) as $lookup) {
            if (($lookup['type'] ?? 0) !== 1) {
                continue;
            }
            $substitutes = $lookup['substitutes'] ?? [];
            $current = $gids[$index];
            if (self::isMatraGlyph($current, $font)) {
                continue;
            }
            $replacement = $substitutes[$current] ?? null;
            if ($replacement !== null && $replacement > 0) {
                $gids[$index] = $replacement;
            }
        }

        return $gids;
    }

    /**
     * @param  list<int>  $gids
     * @param  array<int, list<int>>  $sequences
     * @return list<int>
     */
    protected static function applyMultiple(array $gids, array $sequences): array
    {
        $i = 0;
        while ($i < count($gids)) {
            $sequence = $sequences[$gids[$i]] ?? null;
            if (is_array($sequence) && $sequence !== []) {
                array_splice($gids, $i, 1, $sequence);
                $i += count($sequence);

                continue;
            }
            $i++;
        }

        return $gids;
    }

    /**
     * After Indic reorder, left matras sit before their consonant.
     * Logical C+ে / C+ি ligatures would steal the matra onto the previous letter
     * (পরের → পেরর). Keep ে+C / ি+C and drop C+left-matra.
     *
     * @param  array<int, list<array{components: list<int>, glyph: int}>>  $ligatures
     * @return array<int, list<array{components: list<int>, glyph: int}>>
     */
    protected static function keepReorderedLeftMatraLigatures(array $ligatures, TtfFont $font): array
    {
        $kept = [];
        foreach ($ligatures as $first => $candidates) {
            $usable = [];
            foreach ($candidates as $ligature) {
                $components = $ligature['components'];
                $containsLeftMatra = false;
                foreach ($components as $gid) {
                    if (self::isLeftMatraGlyph((int) $gid, $font)) {
                        $containsLeftMatra = true;
                        break;
                    }
                }
                if ($containsLeftMatra && ! self::isLeftMatraGlyph((int) $first, $font)) {
                    continue;
                }
                $usable[] = $ligature;
            }
            if ($usable !== []) {
                $kept[(int) $first] = $usable;
            }
        }

        return $kept;
    }

    /**
     * @param  list<int>  $gids
     * @param  array<int, list<array{components: list<int>, glyph: int}>>  $ligatures
     * @return list<int>
     */
    protected static function applyLigatures(array $gids, array $ligatures, ?TtfFont $reorderedFont = null): array
    {
        $i = 0;
        while ($i < count($gids)) {
            $first = $gids[$i];
            $candidates = $ligatures[$first] ?? [];
            usort($candidates, fn (array $a, array $b): int => count($b['components']) <=> count($a['components']));

            $matched = false;
            foreach ($candidates as $ligature) {
                $components = $ligature['components'];
                $need = count($components);
                $slice = array_slice($gids, $i + 1, $need);
                if ($slice !== $components || $ligature['glyph'] <= 0) {
                    continue;
                }
                // ে+জ+্+ঞ must become ে+জ্ঞ, not ে+জ.
                if (
                    $reorderedFont
                    && self::isLeftMatraGlyph($first, $reorderedFont)
                    && isset($gids[$i + $need + 1])
                    && self::isViramaGlyph($gids[$i + $need + 1], $reorderedFont)
                ) {
                    continue;
                }
                array_splice($gids, $i, $need + 1, [$ligature['glyph']]);
                $matched = true;
                break;
            }

            if (! $matched) {
                $i++;
            }
        }

        return $gids;
    }

    protected static function isViramaGlyph(int $gid, TtfFont $font): bool
    {
        foreach ([0x094D, 0x09CD, 0x0A4D, 0x0ACD, 0x0B4D, 0x0BCD, 0x0C4D, 0x0CCD, 0x0D4D] as $cp) {
            if ($gid > 0 && $gid === $font->glyphId($cp)) {
                return true;
            }
        }

        return false;
    }

    protected static function isIndicCombining(int $codepoint): bool
    {
        return ($codepoint >= 0x0900 && $codepoint <= 0x0903)
            || ($codepoint >= 0x093A && $codepoint <= 0x094F)
            || ($codepoint >= 0x0951 && $codepoint <= 0x0957)
            || ($codepoint >= 0x0962 && $codepoint <= 0x0963)
            || ($codepoint >= 0x0981 && $codepoint <= 0x0983)
            || $codepoint === 0x09BC
            || ($codepoint >= 0x09BE && $codepoint <= 0x09CD)
            || $codepoint === 0x09D7
            || ($codepoint >= 0x09E2 && $codepoint <= 0x09E3)
            || ($codepoint >= 0x0A01 && $codepoint <= 0x0A03)
            || ($codepoint >= 0x0A3C && $codepoint <= 0x0A4D)
            || ($codepoint >= 0x0A81 && $codepoint <= 0x0A83)
            || ($codepoint >= 0x0ABC && $codepoint <= 0x0ACD)
            || ($codepoint >= 0x0BBE && $codepoint <= 0x0BCD)
            || ($codepoint >= 0x0D3E && $codepoint <= 0x0D4D);
    }
}
