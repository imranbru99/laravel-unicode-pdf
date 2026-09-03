<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Tests\Feature;

use ImranDev\UnicodePdf\Facades\UnicodePdf;
use ImranDev\UnicodePdf\Tests\TestCase;
use ImranDev\UnicodePdf\UnicodePdfManager;

class UnicodePdfManagerTest extends TestCase
{
    public function test_facade_and_container_resolution(): void
    {
        $manager = $this->app->make('unicode-pdf');
        $this->assertInstanceOf(UnicodePdfManager::class, $manager);

        $doc = UnicodePdf::loadHtml('<h1>Hello World</h1>');
        $this->assertNotNull($doc);
    }

    public function test_fluent_configuration_chaining(): void
    {
        $pdf = UnicodePdf::engine('null')
            ->font('Noto Sans Bengali')
            ->fallback(['Noto Sans', 'Noto Sans Bengali'])
            ->direction('auto')
            ->setPaper('A4', 'portrait')
            ->setMargins(15, 15, 15, 15)
            ->watermark('CONFIDENTIAL')
            ->metadata(['title' => 'Invoice #101', 'author' => 'ImranDev'])
            ->loadHtml('<h1>বাংলা ইনভয়েস</h1>');

        $output = $pdf->output();

        $this->assertNotEmpty($output);
        $this->assertStringStartsWith('%PDF-', $output);
        $this->assertStringEndsWith('%%EOF', $output);
    }

    public function test_save_and_download_responses(): void
    {
        $tempPath = sys_get_temp_dir().'/test_output_'.uniqid().'.pdf';

        $saved = UnicodePdf::loadHtml('<h1>Save Test</h1>')->save($tempPath);

        $this->assertTrue($saved);
        $this->assertFileExists($tempPath);

        $downloadResponse = UnicodePdf::loadHtml('<h1>Download Test</h1>')->download('বাংলা.pdf');
        $this->assertEquals(200, $downloadResponse->getStatusCode());
        $this->assertEquals('application/pdf', $downloadResponse->headers->get('Content-Type'));
        $this->assertStringContainsString("filename*=UTF-8''", (string) $downloadResponse->headers->get('Content-Disposition'));

        @unlink($tempPath);
    }
}
