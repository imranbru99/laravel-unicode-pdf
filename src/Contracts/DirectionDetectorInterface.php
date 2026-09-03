<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Contracts;

interface DirectionDetectorInterface
{
    /**
     * Detect dominant text direction: 'ltr', 'rtl', or 'auto'.
     */
    public function detect(string $text): string;

    /**
     * Check if text contains RTL characters (Arabic, Hebrew, Persian, Urdu, etc.).
     */
    public function isRtl(string $text): bool;

    /**
     * Check if text contains mixed LTR and RTL scripts.
     */
    public function isMixed(string $text): bool;
}
