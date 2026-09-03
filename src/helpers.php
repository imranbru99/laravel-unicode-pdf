<?php

declare(strict_types=1);

use ImranDev\UnicodePdf\UnicodePdfDocument;
use ImranDev\UnicodePdf\UnicodePdfManager;

if (! function_exists('unicode_pdf')) {
    /**
     * Resolve the Unicode PDF manager, or a document preloaded with HTML.
     */
    function unicode_pdf(?string $html = null): UnicodePdfDocument|UnicodePdfManager
    {
        /** @var UnicodePdfManager $manager */
        $manager = app('unicode-pdf');

        return $html === null ? $manager : $manager->loadHtml($html);
    }
}
