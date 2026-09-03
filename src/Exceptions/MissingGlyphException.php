<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Exceptions;

class MissingGlyphException extends UnicodePdfException
{
    /**
     * @param  array<string>  $missingCharacters
     * @param  array<string>  $suggestedFonts
     */
    public static function forGlyphs(array $missingCharacters, string $font, array $suggestedFonts = []): self
    {
        $charsStr = implode(', ', array_slice($missingCharacters, 0, 10));
        if (count($missingCharacters) > 10) {
            $charsStr .= '... ('.count($missingCharacters).' total)';
        }

        $message = "Font \"{$font}\" is missing glyphs for characters: [{$charsStr}].";
        if (! empty($suggestedFonts)) {
            $message .= ' Suggested fallback fonts: '.implode(', ', $suggestedFonts).'.';
        }

        return new self($message);
    }
}
