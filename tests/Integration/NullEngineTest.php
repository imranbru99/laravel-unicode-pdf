<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Tests\Integration;

use ImranDev\UnicodePdf\Engines\NullEngine;
use ImranDev\UnicodePdf\Tests\TestCase;

class NullEngineTest extends TestCase
{
    public function test_null_engine_generates_valid_pdf_structure(): void
    {
        $engine = new NullEngine;
        $engine->loadHtml('<h1>Test Title</h1><p>বাংলা ও العربية</p>');

        $output = $engine->output();

        $this->assertStringStartsWith('%PDF-1.4', $output);
        $this->assertStringContainsString('/Root 1 0 R', $output);
        $this->assertStringContainsString('trailer', $output);
        $this->assertStringEndsWith('%%EOF', $output);
    }
}
