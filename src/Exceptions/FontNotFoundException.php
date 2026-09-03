<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Exceptions;

class FontNotFoundException extends UnicodePdfException
{
    public static function forFamily(string $family, ?string $script = null): self
    {
        $message = "The font \"{$family}\" could not be found or is not registered.";
        if ($script) {
            $message .= " Required for detected script: \"{$script}\".";
        }
        $message .= ' Register it using UnicodePdf::registerFont() or configure fonts in config/unicode-pdf.php.';

        return new self($message);
    }
}
