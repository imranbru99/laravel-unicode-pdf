<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Native;

use ImranDev\UnicodePdf\Exceptions\FontNotFoundException;

class TtfFont
{
    /**
     * @var array<string, self>
     */
    protected static array $cache = [];

    /**
     * @var array<string, array{offset: int, length: int}>
     */
    protected array $tables = [];

    /**
     * @var array<int, int>
     */
    protected array $cmap = [];

    /**
     * @var array<int, int>
     */
    protected array $advances = [];

    /**
     * @var list<array<string, mixed>>
     */
    protected array $lookups = [];

    /**
     * @var array<int, list<array<string, mixed>>>
     */
    protected array $lookupsByIndex = [];

    /**
     * Feature tag → GSUB lookup indices in Feature-table order.
     *
     * @var array<string, list<int>>
     */
    protected array $featureLookups = [];

    /**
     * @var array<int, true>
     */
    protected array $markGlyphs = [];

    /**
     * @var list<array<string, mixed>>
     */
    protected array $markToBase = [];

    /**
     * @var list<array<string, mixed>>
     */
    protected array $gposLookups = [];

    /**
     * @var array<int, list<array<string, mixed>>>
     */
    protected array $gposByIndex = [];

    /**
     * @var array<string, list<int>>
     */
    protected array $gposFeatureLookups = [];

    public string $family;

    public string $postscriptName;

    public int $unitsPerEm;

    public int $ascent;

    public int $descent;

    /**
     * @var array{0: int, 1: int, 2: int, 3: int}
     */
    public array $bbox;

    public int $numGlyphs;

    public readonly string $raw;

    public function __construct(public readonly string $path)
    {
        if (! is_readable($path)) {
            throw new FontNotFoundException("Font file is not readable: {$path}");
        }

        $raw = file_get_contents($path);
        if ($raw === false || $raw === '') {
            throw new FontNotFoundException("Unable to read font file: {$path}");
        }

        if (str_starts_with($raw, 'ttcf')) {
            $raw = self::extractCollectionFace($raw);
        }

        $this->raw = $raw;
        $reader = new BinaryReader($raw);
        $sfnt = $reader->bytes(4);

        if (! in_array($sfnt, ["\x00\x01\x00\x00", 'true', 'typ1'], true)) {
            throw new FontNotFoundException("Only TrueType (.ttf) fonts can be embedded natively: {$path}");
        }

        $numTables = $reader->u16();
        $reader->skip(6);

        for ($i = 0; $i < $numTables; $i++) {
            $tag = $reader->tag();
            $reader->skip(4);
            $offset = $reader->u32();
            $length = $reader->u32();
            $this->tables[$tag] = ['offset' => $offset, 'length' => $length];
        }

        $this->parseHead($reader);
        $this->parseHhea($reader);
        $this->parseMaxp($reader);
        $this->parseHmtx($reader);
        $this->parseCmap($reader);
        $this->parseName($reader);
        $this->parseGdef($reader);
        $this->parseGsub($reader);
        $this->parseGpos($reader);
    }

    public static function load(string $path): self
    {
        $real = realpath($path) ?: $path;

        return self::$cache[$real] ??= new self($real);
    }

    /**
     * Rebuild a standalone TTF from the first face of a TrueType Collection.
     */
    public static function extractCollectionFace(string $collection, int $index = 0): string
    {
        if (! str_starts_with($collection, 'ttcf') || strlen($collection) < 16) {
            throw new FontNotFoundException('Not a TrueType Collection.');
        }

        $numFonts = unpack('N', substr($collection, 8, 4))[1];
        if ($index < 0 || $index >= $numFonts) {
            throw new FontNotFoundException("Font collection has no face at index {$index}.");
        }

        $faceOffset = unpack('N', substr($collection, 12 + ($index * 4), 4))[1];
        $sfntHeader = substr($collection, $faceOffset, 12);
        $numTables = unpack('n', substr($collection, $faceOffset + 4, 2))[1];

        $directory = '';
        $payload = '';
        $cursor = 12 + ($numTables * 16);

        for ($i = 0; $i < $numTables; $i++) {
            $entry = $faceOffset + 12 + ($i * 16);
            $tag = substr($collection, $entry, 4);
            $checkSum = substr($collection, $entry + 4, 4);
            $tableOffset = unpack('N', substr($collection, $entry + 8, 4))[1];
            $tableLength = unpack('N', substr($collection, $entry + 12, 4))[1];
            $data = substr($collection, $tableOffset, $tableLength);
            $pad = (4 - ($tableLength % 4)) % 4;

            $directory .= $tag.$checkSum.pack('N', $cursor).pack('N', $tableLength);
            $payload .= $data.str_repeat("\0", $pad);
            $cursor += $tableLength + $pad;
        }

        return $sfntHeader.$directory.$payload;
    }

    public function hasGlyph(int $codepoint): bool
    {
        return isset($this->cmap[$codepoint]);
    }

    public function glyphId(int $codepoint): int
    {
        return $this->cmap[$codepoint] ?? 0;
    }

    public function advance(int $gid, float $fontSize): float
    {
        $units = $this->advances[$gid] ?? ($this->advances[0] ?? $this->unitsPerEm);

        return $units * $fontSize / $this->unitsPerEm;
    }

    public function pdfWidth(int $gid): int
    {
        $units = $this->advances[$gid] ?? ($this->advances[0] ?? $this->unitsPerEm);

        return (int) round($units * 1000 / $this->unitsPerEm);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function lookups(): array
    {
        return $this->lookups;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function lookupsAt(int $index): array
    {
        return $this->lookupsByIndex[$index] ?? [];
    }

    /**
     * Lookups for the given features, in OpenType Feature-table order.
     *
     * @param  list<string>  $features
     * @return list<array<string, mixed>>
     */
    public function lookupsForFeatures(array $features): array
    {
        $seen = [];
        $out = [];

        foreach ($features as $tag) {
            foreach ($this->featureLookups[$tag] ?? [] as $index) {
                $key = $tag.':'.$index;
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                foreach ($this->lookupsByIndex[$index] ?? [] as $lookup) {
                    $out[] = $lookup;
                }
            }
        }

        return $out;
    }

    /**
     * @param  list<string>  $features
     * @return list<array<string, mixed>>
     */
    public function gposLookupsForFeatures(array $features): array
    {
        $seen = [];
        $out = [];

        foreach ($features as $tag) {
            foreach ($this->gposFeatureLookups[$tag] ?? [] as $index) {
                $key = $tag.':'.$index;
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                foreach ($this->gposByIndex[$index] ?? [] as $lookup) {
                    $out[] = $lookup;
                }
            }
        }

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function gposLookupsAt(int $index): array
    {
        return $this->gposByIndex[$index] ?? [];
    }

    public function isMarkGlyph(int $gid): bool
    {
        return isset($this->markGlyphs[$gid]);
    }

    /**
     * @return array{0: int, 1: int}|null x/y offsets in font units
     */
    public function markAttachment(int $baseGid, int $markGid): ?array
    {
        foreach ($this->markToBase as $table) {
            if (! isset($table['markCov'][$markGid], $table['baseCov'][$baseGid])) {
                continue;
            }
            $mark = $table['marks'][$table['markCov'][$markGid]] ?? null;
            if (! is_array($mark)) {
                continue;
            }
            $base = $table['bases'][$table['baseCov'][$baseGid]][$mark['class']] ?? null;
            if (! is_array($base)) {
                continue;
            }

            return [$base[0] - $mark['x'], $base[1] - $mark['y']];
        }

        return null;
    }

    /**
     * @return array<int, int>
     */
    public function cmap(): array
    {
        return $this->cmap;
    }

    protected function parseHead(BinaryReader $reader): void
    {
        $table = $this->tables['head'] ?? null;
        if (! $table) {
            throw new FontNotFoundException("Font is missing the head table: {$this->path}");
        }

        $reader->seek($table['offset'] + 18);
        $units = $reader->u16();
        $reader->skip(16);
        $xMin = $reader->i16();
        $yMin = $reader->i16();
        $xMax = $reader->i16();
        $yMax = $reader->i16();

        $this->unitsPerEm = $units > 0 ? $units : 1000;
        $this->bbox = [$xMin, $yMin, $xMax, $yMax];
    }

    protected function parseHhea(BinaryReader $reader): void
    {
        $table = $this->tables['hhea'] ?? null;
        if (! $table) {
            $this->ascent = 800;
            $this->descent = -200;

            return;
        }

        $reader->seek($table['offset'] + 4);
        $this->ascent = $reader->i16();
        $this->descent = $reader->i16();
    }

    protected function parseMaxp(BinaryReader $reader): void
    {
        $table = $this->tables['maxp'] ?? null;
        if (! $table) {
            $this->numGlyphs = 0;

            return;
        }

        $reader->seek($table['offset'] + 4);
        $this->numGlyphs = $reader->u16();
    }

    protected function parseHmtx(BinaryReader $reader): void
    {
        $table = $this->tables['hmtx'] ?? null;
        $hhea = $this->tables['hhea'] ?? null;
        if (! $table || ! $hhea) {
            return;
        }

        $reader->seek($hhea['offset'] + 34);
        $numberOfHMetrics = $reader->u16();
        $reader->seek($table['offset']);

        $lastAdvance = 0;
        for ($i = 0; $i < $numberOfHMetrics; $i++) {
            $lastAdvance = $reader->u16();
            $reader->skip(2);
            $this->advances[$i] = $lastAdvance;
        }

        for ($i = $numberOfHMetrics; $i < $this->numGlyphs; $i++) {
            $this->advances[$i] = $lastAdvance;
            if ($reader->remaining() >= 2) {
                $reader->skip(2);
            }
        }
    }

    protected function parseCmap(BinaryReader $reader): void
    {
        $table = $this->tables['cmap'] ?? null;
        if (! $table) {
            return;
        }

        $reader->seek($table['offset']);
        $reader->skip(2);
        $numTables = $reader->u16();

        $records = [];
        for ($i = 0; $i < $numTables; $i++) {
            $platform = $reader->u16();
            $encoding = $reader->u16();
            $offset = $reader->u32();
            $records[] = [$platform, $encoding, $table['offset'] + $offset];
        }

        usort($records, function (array $a, array $b): int {
            $score = static fn (array $rec): int => match (true) {
                $rec[0] === 3 && $rec[1] === 10 => 0,
                $rec[0] === 0 && $rec[1] === 4 => 1,
                $rec[0] === 3 && $rec[1] === 1 => 2,
                $rec[0] === 0 => 3,
                default => 9,
            };

            return $score($a) <=> $score($b);
        });

        foreach ($records as [, , $offset]) {
            $reader->seek($offset);
            $format = $reader->u16();
            if ($format === 4) {
                $this->parseCmapFormat4($reader);
                if ($this->cmap !== []) {
                    return;
                }
            } elseif ($format === 12) {
                $this->parseCmapFormat12($reader);
                if ($this->cmap !== []) {
                    return;
                }
            }
        }
    }

    protected function parseCmapFormat4(BinaryReader $reader): void
    {
        $reader->skip(4);
        $segCount = intdiv($reader->u16(), 2);
        $reader->skip(6);

        $endCode = [];
        for ($i = 0; $i < $segCount; $i++) {
            $endCode[] = $reader->u16();
        }
        $reader->skip(2);
        $startCode = [];
        for ($i = 0; $i < $segCount; $i++) {
            $startCode[] = $reader->u16();
        }
        $idDelta = [];
        for ($i = 0; $i < $segCount; $i++) {
            $idDelta[] = $reader->i16();
        }
        $idRangeOffsetPos = $reader->offset;
        $idRangeOffset = [];
        for ($i = 0; $i < $segCount; $i++) {
            $idRangeOffset[] = $reader->u16();
        }

        for ($i = 0; $i < $segCount; $i++) {
            $start = $startCode[$i];
            $end = $endCode[$i];
            if ($start === 0xFFFF && $end === 0xFFFF) {
                continue;
            }
            for ($cp = $start; $cp <= $end; $cp++) {
                if ($idRangeOffset[$i] === 0) {
                    $gid = ($cp + $idDelta[$i]) & 0xFFFF;
                } else {
                    $glyphIndexOffset = $idRangeOffsetPos + (2 * $i) + $idRangeOffset[$i] + (2 * ($cp - $start));
                    $peek = unpack('n', substr($this->raw, $glyphIndexOffset, 2));
                    $glyphId = $peek[1] ?? 0;
                    $gid = $glyphId === 0 ? 0 : (($glyphId + $idDelta[$i]) & 0xFFFF);
                }
                if ($gid !== 0) {
                    $this->cmap[$cp] = $gid;
                }
            }
        }
    }

    protected function parseCmapFormat12(BinaryReader $reader): void
    {
        $reader->skip(10);
        $nGroups = $reader->u32();
        for ($i = 0; $i < $nGroups; $i++) {
            $start = $reader->u32();
            $end = $reader->u32();
            $startGlyph = $reader->u32();
            for ($cp = $start; $cp <= $end; $cp++) {
                $gid = $startGlyph + ($cp - $start);
                if ($gid !== 0) {
                    $this->cmap[$cp] = $gid;
                }
            }
        }
    }

    protected function parseName(BinaryReader $reader): void
    {
        $family = pathinfo($this->path, PATHINFO_FILENAME);
        $postscript = preg_replace('/[^A-Za-z0-9]/', '', $family) ?: 'EmbeddedFont';
        $table = $this->tables['name'] ?? null;

        if ($table) {
            $reader->seek($table['offset']);
            $reader->skip(2);
            $count = $reader->u16();
            $stringOffset = $reader->u16();
            $names = [];

            for ($i = 0; $i < $count; $i++) {
                $platform = $reader->u16();
                $reader->skip(2);
                $reader->skip(2);
                $nameId = $reader->u16();
                $length = $reader->u16();
                $offset = $reader->u16();
                $raw = substr($this->raw, $table['offset'] + $stringOffset + $offset, $length);
                if ($platform === 3 || $platform === 0) {
                    $converted = @iconv('UTF-16BE', 'UTF-8//IGNORE', $raw);
                    $str = $converted !== false ? $converted : mb_convert_encoding($raw, 'UTF-8', 'UTF-16BE');
                } else {
                    $str = $raw;
                }
                if ($str !== '' && ! isset($names[$nameId])) {
                    $names[$nameId] = trim($str);
                }
            }

            $family = $names[1] ?? $family;
            $postscript = $names[6] ?? preg_replace('/[^A-Za-z0-9]/', '', $family) ?: $postscript;
        }

        $this->family = $family;
        $this->postscriptName = (string) $postscript;
    }

    protected function parseGsub(BinaryReader $reader): void
    {
        $table = $this->tables['GSUB'] ?? null;
        if (! $table) {
            return;
        }

        try {
            $base = $table['offset'];
            $reader->seek($base);
            $major = $reader->u16();
            $minor = $reader->u16();
            if ($major !== 1) {
                return;
            }
            $scriptList = $base + $reader->u16();
            $featureList = $base + $reader->u16();
            $lookupList = $base + $reader->u16();
            if ($minor >= 1) {
                $reader->skip(4);
            }

            $featureByLookup = $this->parseFeatureList($reader, $featureList);
            $this->parseLookupList($reader, $lookupList, $featureByLookup);
        } catch (\Throwable) {
            $this->lookups = [];
        }
    }

    /**
     * @return array<int, list<string>>
     */
    protected function parseFeatureList(BinaryReader $reader, int $offset): array
    {
        $reader->seek($offset);
        $count = $reader->u16();
        $records = [];
        for ($i = 0; $i < $count; $i++) {
            $tag = $reader->tag();
            $featureOffset = $offset + $reader->u16();
            $records[] = [$tag, $featureOffset];
        }

        $map = [];
        foreach ($records as [$tag, $featureOffset]) {
            $reader->seek($featureOffset);
            $reader->skip(2);
            $lookupCount = $reader->u16();
            $seen = [];
            for ($i = 0; $i < $lookupCount; $i++) {
                $index = $reader->u16();
                $map[$index][] = $tag;
                if (! isset($seen[$index])) {
                    $seen[$index] = true;
                    $this->featureLookups[$tag][] = $index;
                }
            }
        }

        return $map;
    }

    /**
     * @param  array<int, list<string>>  $featureByLookup
     */
    protected function parseLookupList(BinaryReader $reader, int $offset, array $featureByLookup): void
    {
        $reader->seek($offset);
        $count = $reader->u16();
        $lookupOffsets = [];
        for ($i = 0; $i < $count; $i++) {
            $lookupOffsets[] = $offset + $reader->u16();
        }

        foreach ($lookupOffsets as $index => $lookupOffset) {
            $reader->seek($lookupOffset);
            $type = $reader->u16();
            $flag = $reader->u16();
            $subCount = $reader->u16();
            $subOffsets = [];
            for ($s = 0; $s < $subCount; $s++) {
                $subOffsets[] = $lookupOffset + $reader->u16();
            }
            if (($flag & 0x0010) !== 0) {
                $reader->skip(2);
            }

            $features = $featureByLookup[$index] ?? [];

            if ($type === 7) {
                foreach ($subOffsets as $subOffset) {
                    $reader->seek($subOffset);
                    $reader->skip(2);
                    $extType = $reader->u16();
                    $extOffset = $reader->u32();
                    try {
                        $this->storeLookup($reader, $extType, $subOffset + $extOffset, $features, $index, $flag);
                    } catch (\Throwable) {
                        continue;
                    }
                }

                continue;
            }

            if (! in_array($type, [1, 2, 4, 5, 6], true)) {
                continue;
            }

            foreach ($subOffsets as $subOffset) {
                try {
                    $this->storeLookup($reader, $type, $subOffset, $features, $index, $flag);
                } catch (\Throwable) {
                    continue;
                }
            }
        }
    }

    /**
     * @param  list<string>  $features
     */
    protected function storeLookup(BinaryReader $reader, int $type, int $offset, array $features, int $index = 0, int $flag = 0): void
    {
        $parsed = match ($type) {
            1 => $this->parseSingleSubst($reader, $offset),
            2 => $this->parseMultipleSubst($reader, $offset),
            4 => $this->parseLigatureSubst($reader, $offset),
            5 => $this->parseContextSubst($reader, $offset),
            6 => $this->parseChainContext($reader, $offset),
            default => null,
        };

        if ($parsed !== null) {
            $parsed['features'] = $features;
            $parsed['flag'] = $flag;
            $parsed['index'] = $index;
            $this->lookups[] = $parsed;
            $this->lookupsByIndex[$index][] = $parsed;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function parseSingleSubst(BinaryReader $reader, int $offset): ?array
    {
        $reader->seek($offset);
        $format = $reader->u16();
        $coverageOffset = $offset + $reader->u16();
        $coverage = $this->parseCoverage($reader, $coverageOffset);
        if ($coverage === []) {
            return null;
        }

        $substitutes = [];
        if ($format === 1) {
            $delta = $reader->i16();
            foreach ($coverage as $gid => $_) {
                $substitutes[$gid] = ($gid + $delta) & 0xFFFF;
            }
        } elseif ($format === 2) {
            $count = $reader->u16();
            $gids = array_keys($coverage);
            for ($i = 0; $i < $count; $i++) {
                $substitutes[$gids[$i] ?? 0] = $reader->u16();
            }
        } else {
            return null;
        }

        return ['type' => 1, 'substitutes' => $substitutes];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function parseMultipleSubst(BinaryReader $reader, int $offset): ?array
    {
        $reader->seek($offset);
        if ($reader->u16() !== 1) {
            return null;
        }

        $coverageOffset = $offset + $reader->u16();
        $count = $reader->u16();
        $sequenceOffsets = [];
        for ($i = 0; $i < $count; $i++) {
            $sequenceOffsets[] = $offset + $reader->u16();
        }

        $coverage = $this->parseCoverage($reader, $coverageOffset);
        $sequences = [];
        foreach ($coverage as $gid => $index) {
            if (! isset($sequenceOffsets[$index])) {
                continue;
            }
            $reader->seek($sequenceOffsets[$index]);
            $glyphCount = $reader->u16();
            $substitute = [];
            for ($i = 0; $i < $glyphCount; $i++) {
                $substitute[] = $reader->u16();
            }
            $sequences[$gid] = $substitute;
        }

        return $sequences === [] ? null : ['type' => 2, 'sequences' => $sequences];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function parseLigatureSubst(BinaryReader $reader, int $offset): ?array
    {
        $reader->seek($offset);
        $format = $reader->u16();
        if ($format !== 1) {
            return null;
        }

        $coverageOffset = $offset + $reader->u16();
        $ligSetCount = $reader->u16();
        $ligSetOffsets = [];
        for ($i = 0; $i < $ligSetCount; $i++) {
            $ligSetOffsets[] = $offset + $reader->u16();
        }

        $coverage = $this->parseCoverage($reader, $coverageOffset);
        $byFirst = [];

        foreach ($coverage as $firstGid => $coverageIndex) {
            if (! isset($ligSetOffsets[$coverageIndex])) {
                continue;
            }
            $setOffset = $ligSetOffsets[$coverageIndex];
            $reader->seek($setOffset);
            $ligCount = $reader->u16();
            $ligOffsets = [];
            for ($i = 0; $i < $ligCount; $i++) {
                $ligOffsets[] = $setOffset + $reader->u16();
            }

            foreach ($ligOffsets as $ligOffset) {
                $reader->seek($ligOffset);
                $ligGlyph = $reader->u16();
                $componentCount = $reader->u16();
                $components = [];
                for ($c = 1; $c < $componentCount; $c++) {
                    $components[] = $reader->u16();
                }
                $byFirst[$firstGid][] = [
                    'components' => $components,
                    'glyph' => $ligGlyph,
                ];
            }
        }

        if ($byFirst === []) {
            return null;
        }

        return ['type' => 4, 'ligatures' => $byFirst];
    }

    /**
     * @return array<int, int>
     */
    protected function parseCoverage(BinaryReader $reader, int $offset): array
    {
        $reader->seek($offset);
        $format = $reader->u16();
        $coverage = [];

        if ($format === 1) {
            $count = $reader->u16();
            for ($i = 0; $i < $count; $i++) {
                $coverage[$reader->u16()] = $i;
            }
        } elseif ($format === 2) {
            $count = $reader->u16();
            $index = 0;
            for ($i = 0; $i < $count; $i++) {
                $start = $reader->u16();
                $end = $reader->u16();
                $startIndex = $reader->u16();
                for ($gid = $start; $gid <= $end; $gid++) {
                    $coverage[$gid] = $startIndex + ($gid - $start);
                    $index++;
                }
            }
        }

        return $coverage;
    }

    /**
     * @return array<string, mixed>|null
     */
    /**
     * @return array<string, mixed>|null
     */
    protected function parseContextSubst(BinaryReader $reader, int $offset): ?array
    {
        $reader->seek($offset);
        $format = $reader->u16();

        if ($format === 3) {
            $inputCount = $reader->u16();
            $substCount = $reader->u16();
            $inputOff = [];
            for ($i = 0; $i < $inputCount; $i++) {
                $inputOff[] = $offset + $reader->u16();
            }
            $substs = [];
            for ($i = 0; $i < $substCount; $i++) {
                $substs[] = ['seq' => $reader->u16(), 'lookup' => $reader->u16()];
            }
            $input = [];
            foreach ($inputOff as $coverageOffset) {
                $input[] = $this->parseCoverage($reader, $coverageOffset);
            }

            return ['type' => 5, 'format' => 3, 'input' => $input, 'backtrack' => [], 'lookahead' => [], 'substs' => $substs];
        }

        if ($format !== 1) {
            return null;
        }

        $coverageOffset = $offset + $reader->u16();
        $setCount = $reader->u16();
        $setOffsets = [];
        for ($i = 0; $i < $setCount; $i++) {
            $setOffsets[] = $offset + $reader->u16();
        }
        $coverage = $this->parseCoverage($reader, $coverageOffset);
        $rules = $this->parseGlyphRules($reader, $coverage, $setOffsets, false);

        return $rules === [] ? null : ['type' => 5, 'format' => 1, 'rules' => $rules];
    }

    protected function parseChainContext(BinaryReader $reader, int $offset): ?array
    {
        $reader->seek($offset);
        $format = $reader->u16();

        if ($format === 3) {
            $backtrackCount = $reader->u16();
            $backtrackOff = [];
            for ($i = 0; $i < $backtrackCount; $i++) {
                $backtrackOff[] = $offset + $reader->u16();
            }
            $inputCount = $reader->u16();
            $inputOff = [];
            for ($i = 0; $i < $inputCount; $i++) {
                $inputOff[] = $offset + $reader->u16();
            }
            $lookaheadCount = $reader->u16();
            $lookaheadOff = [];
            for ($i = 0; $i < $lookaheadCount; $i++) {
                $lookaheadOff[] = $offset + $reader->u16();
            }
            $substCount = $reader->u16();
            $substs = [];
            for ($i = 0; $i < $substCount; $i++) {
                $substs[] = [
                    'seq' => $reader->u16(),
                    'lookup' => $reader->u16(),
                ];
            }

            $coverages = function (array $offsets) use ($reader): array {
                $out = [];
                foreach ($offsets as $coverageOffset) {
                    $out[] = $this->parseCoverage($reader, $coverageOffset);
                }

                return $out;
            };

            return [
                'type' => 6,
                'format' => 3,
                'backtrack' => $coverages($backtrackOff),
                'input' => $coverages($inputOff),
                'lookahead' => $coverages($lookaheadOff),
                'substs' => $substs,
            ];
        }

        if ($format === 2) {
            return $this->parseChainClass($reader, $offset);
        }

        if ($format === 1) {
            $coverageOffset = $offset + $reader->u16();
            $setCount = $reader->u16();
            $setOffsets = [];
            for ($i = 0; $i < $setCount; $i++) {
                $setOffsets[] = $offset + $reader->u16();
            }
            $coverage = $this->parseCoverage($reader, $coverageOffset);
            $rules = $this->parseGlyphRules($reader, $coverage, $setOffsets, true);

            return $rules === [] ? null : ['type' => 6, 'format' => 1, 'rules' => $rules];
        }

        return null;
    }

    /**
     * @param  array<int, int>  $coverage
     * @param  list<int>  $setOffsets
     * @return array<int, list<array<string, mixed>>>
     */
    protected function parseGlyphRules(BinaryReader $reader, array $coverage, array $setOffsets, bool $chained): array
    {
        $rules = [];
        foreach ($coverage as $firstGid => $coverageIndex) {
            if (! isset($setOffsets[$coverageIndex])) {
                continue;
            }
            $setOffset = $setOffsets[$coverageIndex];
            $reader->seek($setOffset);
            $ruleCount = $reader->u16();
            $ruleOffsets = [];
            for ($i = 0; $i < $ruleCount; $i++) {
                $ruleOffsets[] = $setOffset + $reader->u16();
            }

            foreach ($ruleOffsets as $ruleOffset) {
                $reader->seek($ruleOffset);
                $backtrack = [];
                if ($chained) {
                    $backtrackCount = $reader->u16();
                    for ($i = 0; $i < $backtrackCount; $i++) {
                        $backtrack[] = $reader->u16();
                    }
                }
                $inputCount = $reader->u16();
                $input = [];
                for ($i = 1; $i < $inputCount; $i++) {
                    $input[] = $reader->u16();
                }
                $lookahead = [];
                if ($chained) {
                    $lookaheadCount = $reader->u16();
                    for ($i = 0; $i < $lookaheadCount; $i++) {
                        $lookahead[] = $reader->u16();
                    }
                }
                $substCount = $reader->u16();
                $substs = [];
                for ($i = 0; $i < $substCount; $i++) {
                    $substs[] = ['seq' => $reader->u16(), 'lookup' => $reader->u16()];
                }
                $rules[$firstGid][] = [
                    'backtrack' => $backtrack,
                    'input' => $input,
                    'lookahead' => $lookahead,
                    'substs' => $substs,
                ];
            }
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function parseChainClass(BinaryReader $reader, int $offset): ?array
    {
        $coverageOffset = $offset + $reader->u16();
        $backtrackDef = $reader->u16();
        $inputDef = $reader->u16();
        $lookaheadDef = $reader->u16();
        $setCount = $reader->u16();
        $setOffsets = [];
        for ($i = 0; $i < $setCount; $i++) {
            $rel = $reader->u16();
            $setOffsets[] = $rel === 0 ? 0 : $offset + $rel;
        }

        $coverage = $this->parseCoverage($reader, $coverageOffset);
        $backtrackClass = $backtrackDef === 0 ? [] : $this->parseClassDef($reader, $offset + $backtrackDef);
        $inputClass = $inputDef === 0 ? [] : $this->parseClassDef($reader, $offset + $inputDef);
        $lookaheadClass = $lookaheadDef === 0 ? [] : $this->parseClassDef($reader, $offset + $lookaheadDef);

        $classSets = [];
        foreach ($setOffsets as $class => $setOffset) {
            if ($setOffset === 0) {
                continue;
            }
            $reader->seek($setOffset);
            $ruleCount = $reader->u16();
            $ruleOffsets = [];
            for ($i = 0; $i < $ruleCount; $i++) {
                $ruleOffsets[] = $setOffset + $reader->u16();
            }
            foreach ($ruleOffsets as $ruleOffset) {
                $reader->seek($ruleOffset);
                $backtrackCount = $reader->u16();
                $backtrack = [];
                for ($i = 0; $i < $backtrackCount; $i++) {
                    $backtrack[] = $reader->u16();
                }
                $inputCount = $reader->u16();
                $input = [];
                for ($i = 1; $i < $inputCount; $i++) {
                    $input[] = $reader->u16();
                }
                $lookaheadCount = $reader->u16();
                $lookahead = [];
                for ($i = 0; $i < $lookaheadCount; $i++) {
                    $lookahead[] = $reader->u16();
                }
                $substCount = $reader->u16();
                $substs = [];
                for ($i = 0; $i < $substCount; $i++) {
                    $substs[] = ['seq' => $reader->u16(), 'lookup' => $reader->u16()];
                }
                $classSets[$class][] = [
                    'backtrack' => $backtrack,
                    'input' => $input,
                    'lookahead' => $lookahead,
                    'substs' => $substs,
                ];
            }
        }

        return [
            'type' => 6,
            'format' => 2,
            'coverage' => $coverage,
            'backtrackClass' => $backtrackClass,
            'inputClass' => $inputClass,
            'lookaheadClass' => $lookaheadClass,
            'classSets' => $classSets,
        ];
    }

    /**
     * @return array<int, int>
     */
    protected function parseClassDef(BinaryReader $reader, int $offset): array
    {
        $reader->seek($offset);
        $format = $reader->u16();
        $map = [];

        if ($format === 1) {
            $start = $reader->u16();
            $count = $reader->u16();
            for ($i = 0; $i < $count; $i++) {
                $map[$start + $i] = $reader->u16();
            }
        } elseif ($format === 2) {
            $count = $reader->u16();
            for ($i = 0; $i < $count; $i++) {
                $start = $reader->u16();
                $end = $reader->u16();
                $class = $reader->u16();
                for ($gid = $start; $gid <= $end; $gid++) {
                    $map[$gid] = $class;
                }
            }
        }

        return $map;
    }

    protected function parseGdef(BinaryReader $reader): void
    {
        $table = $this->tables['GDEF'] ?? null;
        if (! $table) {
            return;
        }

        try {
            $base = $table['offset'];
            $reader->seek($base);
            $major = $reader->u16();
            $minor = $reader->u16();
            if ($major !== 1) {
                return;
            }
            $classOff = $reader->u16();
            if ($classOff === 0) {
                return;
            }
            $this->markGlyphs = $this->parseGlyphClassMarks($reader, $base + $classOff);
            unset($minor);
        } catch (\Throwable) {
            $this->markGlyphs = [];
        }
    }

    /**
     * @return array<int, true>
     */
    protected function parseGlyphClassMarks(BinaryReader $reader, int $offset): array
    {
        $reader->seek($offset);
        $format = $reader->u16();
        $marks = [];

        if ($format === 1) {
            $start = $reader->u16();
            $count = $reader->u16();
            for ($i = 0; $i < $count; $i++) {
                if ($reader->u16() === 3) {
                    $marks[$start + $i] = true;
                }
            }
        } elseif ($format === 2) {
            $count = $reader->u16();
            for ($i = 0; $i < $count; $i++) {
                $start = $reader->u16();
                $end = $reader->u16();
                $class = $reader->u16();
                if ($class !== 3) {
                    continue;
                }
                for ($gid = $start; $gid <= $end; $gid++) {
                    $marks[$gid] = true;
                }
            }
        }

        return $marks;
    }

    protected function parseGpos(BinaryReader $reader): void
    {
        $table = $this->tables['GPOS'] ?? null;
        if (! $table) {
            return;
        }

        try {
            $base = $table['offset'];
            $reader->seek($base);
            $major = $reader->u16();
            $minor = $reader->u16();
            if ($major !== 1) {
                return;
            }
            $reader->u16();
            $featureList = $base + $reader->u16();
            $lookupList = $base + $reader->u16();
            if ($minor >= 1) {
                $reader->skip(4);
            }

            $savedFeatures = $this->featureLookups;
            $this->featureLookups = [];
            $featureByLookup = $this->parseFeatureList($reader, $featureList);
            $this->gposFeatureLookups = $this->featureLookups;
            $this->featureLookups = $savedFeatures;

            $reader->seek($lookupList);
            $count = $reader->u16();
            $lookupOffsets = [];
            for ($i = 0; $i < $count; $i++) {
                $lookupOffsets[] = $lookupList + $reader->u16();
            }

            foreach ($lookupOffsets as $index => $lookupOffset) {
                $reader->seek($lookupOffset);
                $type = $reader->u16();
                $flag = $reader->u16();
                $subCount = $reader->u16();
                $subOffsets = [];
                for ($s = 0; $s < $subCount; $s++) {
                    $subOffsets[] = $lookupOffset + $reader->u16();
                }
                if (($flag & 0x0010) !== 0) {
                    $reader->skip(2);
                }

                $features = $featureByLookup[$index] ?? [];
                foreach ($subOffsets as $subOffset) {
                    $realType = $type;
                    $realOffset = $subOffset;
                    if ($type === 9) {
                        $reader->seek($subOffset);
                        $reader->skip(2);
                        $realType = $reader->u16();
                        $realOffset = $subOffset + $reader->u32();
                    }
                    try {
                        $this->storeGposLookup($reader, $realType, $realOffset, $features, $index, $flag);
                    } catch (\Throwable) {
                        continue;
                    }
                }
            }
        } catch (\Throwable) {
            $this->markToBase = [];
            $this->gposLookups = [];
            $this->gposByIndex = [];
        }
    }

    /**
     * @param  list<string>  $features
     */
    protected function storeGposLookup(BinaryReader $reader, int $type, int $offset, array $features, int $index, int $flag): void
    {
        $parsed = match ($type) {
            1 => $this->parseSinglePos($reader, $offset),
            2 => $this->parsePairPos($reader, $offset),
            4 => $this->parseMarkBasePos($reader, $offset),
            8 => $this->parseChainPos($reader, $offset),
            default => null,
        };

        if ($parsed === null) {
            return;
        }

        $parsed['features'] = $features;
        $parsed['flag'] = $flag;
        $parsed['index'] = $index;
        $this->gposLookups[] = $parsed;
        $this->gposByIndex[$index][] = $parsed;

        if ($type === 4) {
            $this->markToBase[] = $parsed;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function parseSinglePos(BinaryReader $reader, int $offset): ?array
    {
        $reader->seek($offset);
        $format = $reader->u16();
        $coverageOffset = $offset + $reader->u16();
        $valueFormat = $reader->u16();
        $coverage = $this->parseCoverage($reader, $coverageOffset);
        $values = [];

        if ($format === 1) {
            $value = $this->parseValueRecord($reader, $valueFormat);
            foreach ($coverage as $gid => $_) {
                $values[$gid] = $value;
            }
        } elseif ($format === 2) {
            $count = $reader->u16();
            $gids = array_keys($coverage);
            for ($i = 0; $i < $count; $i++) {
                $values[$gids[$i] ?? 0] = $this->parseValueRecord($reader, $valueFormat);
            }
        } else {
            return null;
        }

        return ['type' => 1, 'values' => $values];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function parsePairPos(BinaryReader $reader, int $offset): ?array
    {
        $reader->seek($offset);
        $format = $reader->u16();
        if ($format !== 1) {
            return null;
        }

        $coverageOffset = $offset + $reader->u16();
        $valueFormat1 = $reader->u16();
        $valueFormat2 = $reader->u16();
        $setCount = $reader->u16();
        $setOffsets = [];
        for ($i = 0; $i < $setCount; $i++) {
            $setOffsets[] = $offset + $reader->u16();
        }
        $coverage = $this->parseCoverage($reader, $coverageOffset);
        $pairs = [];

        foreach ($coverage as $first => $coverageIndex) {
            if (! isset($setOffsets[$coverageIndex])) {
                continue;
            }
            $reader->seek($setOffsets[$coverageIndex]);
            $pairCount = $reader->u16();
            for ($i = 0; $i < $pairCount; $i++) {
                $second = $reader->u16();
                $pairs[$first][$second] = [
                    $this->parseValueRecord($reader, $valueFormat1),
                    $this->parseValueRecord($reader, $valueFormat2),
                ];
            }
        }

        return $pairs === [] ? null : ['type' => 2, 'pairs' => $pairs];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function parseChainPos(BinaryReader $reader, int $offset): ?array
    {
        $parsed = $this->parseChainContext($reader, $offset);
        if ($parsed === null) {
            return null;
        }
        $parsed['type'] = 8;

        return $parsed;
    }

    /**
     * @return array{0: int, 1: int, 2: int, 3: int}
     */
    protected function parseValueRecord(BinaryReader $reader, int $format): array
    {
        $x = ($format & 0x0001) !== 0 ? $reader->i16() : 0;
        $y = ($format & 0x0002) !== 0 ? $reader->i16() : 0;
        $xAdv = ($format & 0x0004) !== 0 ? $reader->i16() : 0;
        $yAdv = ($format & 0x0008) !== 0 ? $reader->i16() : 0;
        for ($bit = 0x0010; $bit <= 0x0080; $bit <<= 1) {
            if (($format & $bit) !== 0) {
                $reader->skip(2);
            }
        }

        return [$x, $y, $xAdv, $yAdv];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function parseMarkBasePos(BinaryReader $reader, int $offset): ?array
    {
        $reader->seek($offset);
        if ($reader->u16() !== 1) {
            return null;
        }

        $markCovOff = $offset + $reader->u16();
        $baseCovOff = $offset + $reader->u16();
        $classCount = $reader->u16();
        $markArrayOff = $offset + $reader->u16();
        $baseArrayOff = $offset + $reader->u16();

        $markCov = $this->parseCoverage($reader, $markCovOff);
        $baseCov = $this->parseCoverage($reader, $baseCovOff);

        $reader->seek($markArrayOff);
        $markCount = $reader->u16();
        $markRecs = [];
        for ($i = 0; $i < $markCount; $i++) {
            $class = $reader->u16();
            $markRecs[] = [$class, $markArrayOff + $reader->u16()];
        }
        $marks = [];
        foreach ($markRecs as $i => [$class, $anchorOff]) {
            $anchor = $this->parseAnchor($reader, $anchorOff);
            $marks[$i] = ['class' => $class, 'x' => $anchor[0], 'y' => $anchor[1]];
        }

        $reader->seek($baseArrayOff);
        $baseCount = $reader->u16();
        $baseOffs = [];
        for ($i = 0; $i < $baseCount; $i++) {
            $row = [];
            for ($c = 0; $c < $classCount; $c++) {
                $rel = $reader->u16();
                $row[] = $rel === 0 ? 0 : $baseArrayOff + $rel;
            }
            $baseOffs[] = $row;
        }
        $bases = [];
        foreach ($baseOffs as $i => $row) {
            foreach ($row as $c => $anchorOff) {
                if ($anchorOff !== 0) {
                    $bases[$i][$c] = $this->parseAnchor($reader, $anchorOff);
                }
            }
        }

        return [
            'type' => 4,
            'markCov' => $markCov,
            'baseCov' => $baseCov,
            'marks' => $marks,
            'bases' => $bases,
        ];
    }

    /**
     * @return array{0: int, 1: int}
     */
    protected function parseAnchor(BinaryReader $reader, int $offset): array
    {
        $reader->seek($offset);
        $reader->skip(2);
        $x = $reader->i16();
        $y = $reader->i16();

        return [$x, $y];
    }
}
