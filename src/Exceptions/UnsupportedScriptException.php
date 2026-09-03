<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Exceptions;

class UnsupportedScriptException extends UnicodePdfException
{
    public static function forScript(string $script, string $engine, string $suggestion = ''): self
    {
        $message = "The selected PDF engine \"{$engine}\" does not support complex text shaping for script \"{$script}\".";
        if ($suggestion !== '') {
            $message .= " {$suggestion}";
        } else {
            $message .= ' Consider using mPDF or Browsershot/Chromium for complex scripts (such as Bengali or Devanagari).';
        }

        return new self($message);
    }
}
