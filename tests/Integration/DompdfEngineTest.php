<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Tests\Integration;

use Dompdf\Dompdf;
use ImranDev\UnicodePdf\Engines\DompdfEngine;
use ImranDev\UnicodePdf\Tests\TestCase;

class DompdfEngineTest extends TestCase
{
    public function test_dompdf_engine_generates_pdf_if_installed(): void
    {
        if (! class_exists(Dompdf::class)) {
            $this->markTestSkipped('dompdf/dompdf package is not installed.');
        }

        $engine = new DompdfEngine;
        $engine->loadHtml('<h1>Dompdf Test</h1><p>Unicode text</p>');

        $output = $engine->output();

        $this->assertNotEmpty($output);
        $this->assertStringStartsWith('%PDF-', $output);
    }
}
