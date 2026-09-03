<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Contracts;

interface FontResolverInterface
{
    /**
     * Resolve the most appropriate font family or list of fonts for given text content or scripts.
     *
     * @return array<string>
     */
    public function resolve(string $text, ?string $defaultFont = null): array;

    /**
     * Resolve font family name specifically for a detected script name.
     */
    public function resolveForScript(string $script): ?string;
}
