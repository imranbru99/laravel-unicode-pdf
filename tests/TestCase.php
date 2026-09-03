<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Tests;

use ImranDev\UnicodePdf\Facades\UnicodePdf;
use ImranDev\UnicodePdf\UnicodePdfServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            UnicodePdfServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'UnicodePdf' => UnicodePdf::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('unicode-pdf.engine', 'null');
        $app['config']->set('unicode-pdf.default_font', 'Noto Sans');
        $app['config']->set('unicode-pdf.fallback_fonts', [
            'Noto Sans',
            'Noto Sans Bengali',
            'Noto Sans Arabic',
            'Noto Sans Devanagari',
            'Noto Sans CJK SC',
        ]);
        $app['config']->set('unicode-pdf.font_path', sys_get_temp_dir().'/unicode_pdf_fonts');
        $app['config']->set('unicode-pdf.font_cache', sys_get_temp_dir().'/unicode_pdf_cache');
    }
}
