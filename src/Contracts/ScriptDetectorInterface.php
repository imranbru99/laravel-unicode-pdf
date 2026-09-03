<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Contracts;

interface ScriptDetectorInterface
{
    /**
     * Detect all distinct Unicode scripts present in the text with their occurrence counts.
     *
     * @return array<string, int>
     */
    public function detect(string $text): array;

    /**
     * Get the dominant script name for the text.
     */
    public function getDominantScript(string $text): string;

    /**
     * Check if the text contains characters belonging to a given script.
     */
    public function containsScript(string $text, string $script): bool;
}
