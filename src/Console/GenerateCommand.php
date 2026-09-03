<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Console;

use Illuminate\Console\Command;
use ImranDev\UnicodePdf\UnicodePdfManager;

class GenerateCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'unicode-pdf:generate
                            {view? : Blade view name to render}
                            {--html= : Raw HTML string to render instead of a view}
                            {--output= : Destination file path}
                            {--preset= : Typography preset (bengali, arabic, indian, cjk, universal, ...)}
                            {--engine= : PDF engine (native, dompdf, mpdf, tcpdf, browsershot, null)}
                            {--paper=a4 : Paper size}
                            {--orientation=portrait : Paper orientation}
                            {--filename=document.pdf : Download filename when outputting to stdout is not used}';

    /**
     * The console command description.
     */
    protected $description = 'Generate a Unicode PDF from a Blade view or HTML string';

    public function handle(UnicodePdfManager $manager): int
    {
        $html = $this->option('html');
        $view = $this->argument('view');

        if ((! is_string($html) || $html === '') && (! is_string($view) || $view === '')) {
            $this->error('Provide a Blade view argument or --html=');

            return Command::FAILURE;
        }

        $engine = is_string($this->option('engine')) && $this->option('engine') !== ''
            ? $this->option('engine')
            : null;

        $document = $manager->createDocument($engine);

        $preset = $this->option('preset');
        if (is_string($preset) && $preset !== '') {
            $document->preset($preset);
        }

        $paper = is_string($this->option('paper')) ? $this->option('paper') : 'a4';
        $orientation = is_string($this->option('orientation')) ? $this->option('orientation') : 'portrait';
        $document->setPaper($paper, $orientation);

        if (is_string($html) && $html !== '') {
            $document->loadHtml($html);
        } elseif (is_string($view) && $view !== '') {
            $document->loadView($view);
        }

        $output = $this->option('output');
        if (is_string($output) && $output !== '') {
            $directory = dirname($output);
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            $document->save($output);
            $this->info("PDF written to {$output}");

            return Command::SUCCESS;
        }

        $filename = is_string($this->option('filename')) ? $this->option('filename') : 'document.pdf';
        $directory = storage_path('app/unicode-pdf/generated');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        $path = $directory.DIRECTORY_SEPARATOR.$filename;
        $document->save($path);
        $this->info("PDF written to {$path}");

        return Command::SUCCESS;
    }
}
