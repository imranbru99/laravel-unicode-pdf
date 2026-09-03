<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Tests\Integration;

use ImranDev\UnicodePdf\Engines\MpdfEngine;
use ImranDev\UnicodePdf\Tests\TestCase;
use Mpdf\Mpdf;

class MpdfEngineTest extends TestCase
{
    public function test_mpdf_engine_generates_pdf_if_installed(): void
    {
        if (! class_exists(Mpdf::class)) {
            $this->markTestSkipped('mpdf/mpdf package is not installed.');
        }

        $engine = new MpdfEngine;
        $engine->loadHtml('<h1>mPDF Multilingual Test</h1><p>বাংলা ও العربية</p>');

        $output = $engine->output();

        $this->assertNotEmpty($output);
        $this->assertStringStartsWith('%PDF-', $output);
    }
}
