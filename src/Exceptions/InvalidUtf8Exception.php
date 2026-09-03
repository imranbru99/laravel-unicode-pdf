<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Exceptions;

class InvalidUtf8Exception extends UnicodePdfException
{
    public function __construct(
        string $message,
        protected int $byteOffset = -1,
        protected ?string $snippet = null
    ) {
        parent::__construct($message);
    }

    public static function atOffset(int $offset, string $contextSnippet = ''): self
    {
        $message = "Invalid UTF-8 byte sequence detected at byte position {$offset}.";
        if ($contextSnippet !== '') {
            $message .= " Surrounding context: \"{$contextSnippet}\".";
        }
        $message .= ' Please ensure input strings and views are valid UTF-8 encoded before rendering.';

        return new self($message, $offset, $contextSnippet);
    }

    public function getByteOffset(): int
    {
        return $this->byteOffset;
    }

    public function getSnippet(): ?string
    {
        return $this->snippet;
    }
}
