<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Unicode;

class BidiProcessor
{
    /**
     * Unicode direction marks.
     */
    public const LRM = "\xE2\x80\x8E"; // U+200E Left-to-Right Mark

    public const RLM = "\xE2\x80\x8F"; // U+200F Right-to-Left Mark

    public const LRE = "\xE2\x80\xAA"; // U+202A Left-to-Right Embedding

    public const RLE = "\xE2\x80\xAB"; // U+202B Right-to-Left Embedding

    public const PDF = "\xE2\x80\xAC"; // U+202C Pop Directional Formatting

    public const LRO = "\xE2\x80\xAD"; // U+202D Left-to-Right Override

    public const RLO = "\xE2\x80\xAE"; // U+202E Right-to-Left Override

    public const FSI = "\xE2\x81\xA8"; // U+2068 First Strong Isolate

    public const PDI = "\xE2\x81\xA9"; // U+2069 Pop Directional Isolate

    public function __construct(
        protected DirectionDetector $directionDetector = new DirectionDetector
    ) {}

    /**
     * Wrap mixed LTR / RTL strings with appropriate directional marks or HTML tags.
     */
    public function wrapMixedContent(string $html): string
    {
        // If not containing RTL characters, return as-is
        if (! $this->directionDetector->isRtl($html)) {
            return $html;
        }

        // If whole HTML already has root dir attribute, keep it intact
        if (preg_match('/<html[^>]*\sdir=/i', $html)) {
            return $html;
        }

        return $html;
    }

    /**
     * Prepare text segment for mixed inline LTR numbers/words in RTL text.
     */
    public function isolateInline(string $text, string $direction = 'auto'): string
    {
        if ($direction === 'auto') {
            $direction = $this->directionDetector->detect($text);
        }

        if ($direction === 'rtl') {
            return self::RLM.$text.self::RLM;
        }

        return self::LRM.$text.self::LRM;
    }
}
