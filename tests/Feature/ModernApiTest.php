<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use ImranDev\UnicodePdf\Concerns\GeneratesPdf;
use ImranDev\UnicodePdf\Enums\Engine;
use ImranDev\UnicodePdf\Enums\PaperSize;
use ImranDev\UnicodePdf\Enums\Preset;
use ImranDev\UnicodePdf\Events\PdfGenerated;
use ImranDev\UnicodePdf\Events\PdfGenerating;
use ImranDev\UnicodePdf\Exceptions\PresetNotFoundException;
use ImranDev\UnicodePdf\Facades\UnicodePdf;
use ImranDev\UnicodePdf\Jobs\GeneratePdfJob;
use ImranDev\UnicodePdf\Testing\UnicodePdfFake;
use ImranDev\UnicodePdf\Tests\TestCase;
use ImranDev\UnicodePdf\UnicodePdfDocument;

class ModernApiTest extends TestCase
{
    public function test_conditionable_and_enum_fluent_api(): void
    {
        $pdf = UnicodePdf::engine(Engine::Null)
            ->preset(Preset::Bengali)
            ->paper(PaperSize::A4)
            ->landscape()
            ->title('চালান')
            ->author('Imran')
            ->when(true, fn (UnicodePdfDocument $doc) => $doc->watermark('TEST'))
            ->unless(false, fn (UnicodePdfDocument $doc) => $doc->name('invoice.pdf'))
            ->loadHtml('<h1>বাংলা</h1>');

        $output = $pdf->output();

        $this->assertStringStartsWith('%PDF-', $output);
        $this->assertSame('invoice.pdf', $pdf->getFilename());
        $this->assertSame('bengali', $pdf->getPresetName());
    }

    public function test_unknown_preset_throws(): void
    {
        $this->expectException(PresetNotFoundException::class);

        UnicodePdf::preset('does-not-exist');
    }

    public function test_named_profile_applies_config(): void
    {
        $doc = UnicodePdf::profile('rtl-report')->loadHtml('<p>مرحبا</p>');

        $this->assertInstanceOf(UnicodePdfDocument::class, $doc);
        $this->assertSame('arabic', $doc->getPresetName());
        $this->assertStringStartsWith('%PDF-', $doc->output());
    }

    public function test_helper_and_base64_data_uri(): void
    {
        $doc = unicode_pdf('<p>Hello</p>');
        $this->assertInstanceOf(UnicodePdfDocument::class, $doc);

        $base64 = $doc->base64();
        $this->assertNotEmpty($base64);
        $this->assertStringStartsWith('data:application/pdf;base64,', $doc->dataUri());
    }

    public function test_responsable_downloads_by_default(): void
    {
        $response = UnicodePdf::loadHtml('<p>Invoice</p>')->name('চালান.pdf')->toResponse(request());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_fake_records_generation_and_downloads(): void
    {
        $fake = UnicodePdf::fake();
        $this->assertInstanceOf(UnicodePdfFake::class, $fake);

        UnicodePdf::loadHtml('<p>Test</p>')->download('report.pdf');

        $fake->assertGenerated();
        $fake->assertDownloaded('report.pdf');
    }

    public function test_events_are_dispatched_on_output(): void
    {
        Event::fake([PdfGenerating::class, PdfGenerated::class]);

        UnicodePdf::loadHtml('<p>Event</p>')->output();

        Event::assertDispatched(PdfGenerating::class);
        Event::assertDispatched(PdfGenerated::class);
    }

    public function test_queue_dispatches_generate_job(): void
    {
        Queue::fake();

        UnicodePdf::loadHtml('<p>Queued</p>')->queue('invoices/101.pdf', 'local');

        Queue::assertPushed(GeneratePdfJob::class, function (GeneratePdfJob $job): bool {
            return $job->path === 'invoices/101.pdf' && $job->disk === 'local';
        });
    }

    public function test_locale_aliases_and_extra_presets(): void
    {
        $this->assertInstanceOf(UnicodePdfDocument::class, UnicodePdf::preset('thai'));
        $this->assertInstanceOf(UnicodePdfDocument::class, UnicodePdf::preset('hebrew'));
        $this->assertInstanceOf(UnicodePdfDocument::class, UnicodePdf::preset('urdu'));
        $this->assertInstanceOf(UnicodePdfDocument::class, UnicodePdf::preset('khmer'));
        $this->assertInstanceOf(UnicodePdfDocument::class, UnicodePdf::preset('bn'));
        $this->assertInstanceOf(UnicodePdfDocument::class, UnicodePdf::locale('am'));
    }

    public function test_numerals_helper_on_manager(): void
    {
        $this->assertSame('১২৩', UnicodePdf::numerals('123', 'bn'));
    }

    public function test_generate_command_writes_file(): void
    {
        $output = sys_get_temp_dir().DIRECTORY_SEPARATOR.'unicode-pdf-'.uniqid().'.pdf';

        $this->artisan('unicode-pdf:generate', [
            '--html' => '<h1>বাংলাদেশ</h1>',
            '--output' => $output,
            '--engine' => 'null',
            '--preset' => 'bengali',
        ])->assertSuccessful();

        $this->assertFileExists($output);
        $this->assertStringStartsWith('%PDF-', (string) file_get_contents($output));
        @unlink($output);
    }

    public function test_generates_pdf_concern(): void
    {
        $model = new class
        {
            use GeneratesPdf;

            protected function pdfView(): string
            {
                return 'unicode-pdf::sample-multilingual';
            }

            protected function pdfPreset(): string
            {
                return 'universal';
            }

            protected function pdfFilename(): string
            {
                return 'sample.pdf';
            }
        };

        $doc = $model->toPdf();
        $this->assertInstanceOf(UnicodePdfDocument::class, $doc);
        $this->assertSame('sample.pdf', $doc->getFilename());
        $this->assertStringStartsWith('%PDF-', $doc->output());
    }
}
