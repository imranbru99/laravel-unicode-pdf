<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Fonts;

use ImranDev\UnicodePdf\Exceptions\FontRegistrationException;

class FontMetadata
{
    /**
     * Parse metadata from a TTF or OTF font file.
     *
     * @return array{
     *     family: string,
     *     subfamily: string,
     *     full_name: string,
     *     postscript_name: string,
     *     format: string,
     *     num_glyphs: int,
     *     unicode_ranges: array<int>,
     *     supported_scripts: array<string>
     * }
     */
    public static function parse(string $filePath): array
    {
        if (! file_exists($filePath) || ! is_readable($filePath)) {
            throw FontRegistrationException::fileNotFound('unknown', 'regular', $filePath);
        }

        $fp = fopen($filePath, 'rb');
        if (! $fp) {
            throw FontRegistrationException::invalidFormat('unknown', $filePath);
        }

        try {
            $sfntVersion = fread($fp, 4);
            $format = match ($sfntVersion) {
                "\x00\x01\x00\x00", 'true', 'typ1' => 'TTF',
                'OTTO' => 'OTF',
                'wOFF' => 'WOFF',
                'wOF2' => 'WOFF2',
                default => 'Unknown',
            };

            if ($format === 'Unknown') {
                throw FontRegistrationException::invalidFormat('unknown', $filePath);
            }

            $numTablesData = fread($fp, 2);
            $numTables = unpack('n', $numTablesData)[1] ?? 0;

            // Skip searchRange, entrySelector, rangeShift (6 bytes)
            fseek($fp, 6, SEEK_CUR);

            $tables = [];
            for ($i = 0; $i < $numTables; $i++) {
                $tag = fread($fp, 4);
                $checksum = fread($fp, 4);
                $offset = unpack('N', fread($fp, 4))[1];
                $length = unpack('N', fread($fp, 4))[1];

                $tables[$tag] = [
                    'offset' => $offset,
                    'length' => $length,
                ];
            }

            $names = self::parseNameTable($fp, $tables['name'] ?? null);
            $numGlyphs = self::parseMaxpTable($fp, $tables['maxp'] ?? null);
            $unicodeRanges = self::parseOs2Table($fp, $tables['OS/2'] ?? null);
            $supportedScripts = self::resolveScriptsFromRanges($unicodeRanges);

            $family = $names[1] ?? pathinfo($filePath, PATHINFO_FILENAME);
            $subfamily = $names[2] ?? 'Regular';
            $fullName = $names[4] ?? ($family.' '.$subfamily);
            $postscriptName = $names[6] ?? str_replace(' ', '', $fullName);

            return [
                'family' => $family,
                'subfamily' => $subfamily,
                'full_name' => $fullName,
                'postscript_name' => $postscriptName,
                'format' => $format,
                'num_glyphs' => $numGlyphs,
                'unicode_ranges' => $unicodeRanges,
                'supported_scripts' => $supportedScripts,
            ];
        } finally {
            fclose($fp);
        }
    }

    /**
     * Parse 'name' table strings.
     *
     * @param  resource  $fp
     * @param  array{offset: int, length: int}|null  $table
     * @return array<int, string>
     */
    protected static function parseNameTable($fp, ?array $table): array
    {
        if (! $table) {
            return [];
        }

        fseek($fp, $table['offset']);
        $format = unpack('n', fread($fp, 2))[1] ?? 0;
        $count = unpack('n', fread($fp, 2))[1] ?? 0;
        $stringOffset = unpack('n', fread($fp, 2))[1] ?? 0;

        $nameRecords = [];
        for ($i = 0; $i < $count; $i++) {
            $platformId = unpack('n', fread($fp, 2))[1];
            $encodingId = unpack('n', fread($fp, 2))[1];
            $languageId = unpack('n', fread($fp, 2))[1];
            $nameId = unpack('n', fread($fp, 2))[1];
            $length = unpack('n', fread($fp, 2))[1];
            $offset = unpack('n', fread($fp, 2))[1];

            $nameRecords[] = [
                'platformId' => $platformId,
                'encodingId' => $encodingId,
                'languageId' => $languageId,
                'nameId' => $nameId,
                'length' => $length,
                'offset' => $offset,
            ];
        }

        $names = [];
        $storageOffset = $table['offset'] + $stringOffset;

        foreach ($nameRecords as $rec) {
            fseek($fp, $storageOffset + $rec['offset']);
            $raw = fread($fp, $rec['length']);

            // Windows Unicode (Platform 3) or Mac Roman (Platform 1)
            $str = '';
            if ($rec['platformId'] === 3 || $rec['platformId'] === 0) {
                // UTF-16BE to UTF-8
                $converted = @iconv('UTF-16BE', 'UTF-8//IGNORE', $raw);
                $str = $converted !== false ? $converted : mb_convert_encoding($raw, 'UTF-8', 'UTF-16BE');
            } elseif ($rec['platformId'] === 1) {
                $str = $raw;
            }

            if ($str !== '' && ! isset($names[$rec['nameId']])) {
                $names[$rec['nameId']] = trim($str);
            }
        }

        return $names;
    }

    /**
     * Parse 'maxp' table for number of glyphs.
     *
     * @param  resource  $fp
     * @param  array{offset: int, length: int}|null  $table
     */
    protected static function parseMaxpTable($fp, ?array $table): int
    {
        if (! $table) {
            return 0;
        }

        fseek($fp, $table['offset'] + 4); // skip version (4 bytes)
        $numGlyphs = unpack('n', fread($fp, 2))[1] ?? 0;

        return $numGlyphs;
    }

    /**
     * Parse 'OS/2' table for Unicode Range flags.
     *
     * @param  resource  $fp
     * @param  array{offset: int, length: int}|null  $table
     * @return array<int>
     */
    protected static function parseOs2Table($fp, ?array $table): array
    {
        if (! $table || $table['length'] < 58) {
            return [];
        }

        // ulUnicodeRange1-4 are at offset 42 in OS/2 table
        fseek($fp, $table['offset'] + 42);
        $range1 = unpack('N', fread($fp, 4))[1] ?? 0;
        $range2 = unpack('N', fread($fp, 4))[1] ?? 0;
        $range3 = unpack('N', fread($fp, 4))[1] ?? 0;
        $range4 = unpack('N', fread($fp, 4))[1] ?? 0;

        return [$range1, $range2, $range3, $range4];
    }

    /**
     * Map OS/2 ulUnicodeRange bits to script names.
     *
     * @param  array<int>  $ranges
     * @return array<string>
     */
    protected static function resolveScriptsFromRanges(array $ranges): array
    {
        if (count($ranges) < 4) {
            return ['Latin'];
        }

        [$r1, $r2, $r3, $r4] = $ranges;
        $scripts = [];

        // Range 1 bits
        if ($r1 & (1 << 0)) {
            $scripts[] = 'Latin';
        }
        if ($r1 & (1 << 1)) {
            $scripts[] = 'Latin Extended';
        }
        if ($r1 & (1 << 9)) {
            $scripts[] = 'Cyrillic';
        }
        if ($r1 & (1 << 7)) {
            $scripts[] = 'Greek';
        }
        if ($r1 & (1 << 11)) {
            $scripts[] = 'Hebrew';
        }
        if ($r1 & (1 << 13)) {
            $scripts[] = 'Arabic';
        }
        if ($r1 & (1 << 15)) {
            $scripts[] = 'Devanagari';
        }
        if ($r1 & (1 << 16)) {
            $scripts[] = 'Bengali';
        }
        if ($r1 & (1 << 17)) {
            $scripts[] = 'Gurmukhi';
        }
        if ($r1 & (1 << 18)) {
            $scripts[] = 'Gujarati';
        }
        if ($r1 & (1 << 20)) {
            $scripts[] = 'Tamil';
        }
        if ($r1 & (1 << 21)) {
            $scripts[] = 'Telugu';
        }
        if ($r1 & (1 << 22)) {
            $scripts[] = 'Kannada';
        }
        if ($r1 & (1 << 23)) {
            $scripts[] = 'Malayalam';
        }
        if ($r1 & (1 << 24)) {
            $scripts[] = 'Thai';
        }
        if ($r1 & (1 << 26)) {
            $scripts[] = 'Georgian';
        }

        // Range 2 bits
        if ($r2 & (1 << 27)) {
            $scripts[] = 'CJK';
        }
        if ($r2 & (1 << 28)) {
            $scripts[] = 'Korean';
        }
        if ($r2 & (1 << 17)) {
            $scripts[] = 'Japanese';
        }

        if (empty($scripts)) {
            $scripts[] = 'Latin';
        }

        return array_unique($scripts);
    }
}
