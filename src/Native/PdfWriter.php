<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Native;

class PdfWriter
{
    /**
     * @var array<int, string>
     */
    protected array $objects = [];

    public function add(string $content): int
    {
        $this->objects[] = $content;

        return count($this->objects);
    }

    public function replace(int $id, string $content): void
    {
        if (isset($this->objects[$id - 1])) {
            $this->objects[$id - 1] = $content;
        }
    }

    public function get(int $id): string
    {
        return $this->objects[$id - 1] ?? '';
    }

    /**
     * @param  array<string, string|int|float>  $dictionary
     */
    public function stream(array $dictionary, string $data): int
    {
        if (function_exists('gzcompress')) {
            $compressed = gzcompress($data);
            if ($compressed !== false) {
                $data = $compressed;
                $dictionary['Filter'] = '/FlateDecode';
            }
        }

        $parts = [];
        foreach ($dictionary as $key => $value) {
            $parts[] = '/'.$key.' '.$value;
        }
        $parts[] = '/Length '.strlen($data);

        return $this->add('<< '.implode(' ', $parts)." >>\nstream\n{$data}\nendstream");
    }

    public function finish(int $rootId, ?int $infoId = null): string
    {
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];

        foreach ($this->objects as $index => $body) {
            $id = $index + 1;
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$body}\nendobj\n";
        }

        $xref = strlen($pdf);
        $count = count($this->objects) + 1;
        $pdf .= "xref\n0 {$count}\n0000000000 65535 f \n";

        for ($id = 1; $id < $count; $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id]);
        }

        $info = $infoId ? " /Info {$infoId} 0 R" : '';
        $pdf .= "trailer\n<< /Size {$count} /Root {$rootId} 0 R{$info} >>\n";
        $pdf .= "startxref\n{$xref}\n%%EOF";

        return $pdf;
    }

    public static function escapeLiteral(string $text): string
    {
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '\\r', '\\n'], $text);
    }

    public static function utf16beString(string $text): string
    {
        $utf16 = "\xFE\xFF".mb_convert_encoding($text, 'UTF-16BE', 'UTF-8');

        return '('.self::escapeLiteral($utf16).')';
    }

    /**
     * @param  list<int>  $gids
     */
    public static function hexGids(array $gids): string
    {
        $hex = '';
        foreach ($gids as $gid) {
            $hex .= sprintf('%04X', $gid & 0xFFFF);
        }

        return '<'.$hex.'>';
    }

    /**
     * @param  array{0: float, 1: float, 2: float}  $rgb
     */
    public static function rgb(array $rgb): string
    {
        return sprintf('%.3F %.3F %.3F', $rgb[0], $rgb[1], $rgb[2]);
    }
}
