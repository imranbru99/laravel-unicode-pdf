<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Exceptions;

class FontRegistrationException extends UnicodePdfException
{
    public static function fileNotFound(string $family, string $style, string $path): self
    {
        return new self("Cannot register font family \"{$family}\" ({$style}): file does not exist at \"{$path}\".");
    }

    public static function invalidFormat(string $family, string $path): self
    {
        return new self("Cannot register font family \"{$family}\": file at \"{$path}\" is not a valid TTF, OTF, or supported font binary.");
    }
}
