<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ImranDev\UnicodePdf\UnicodePdfManager;

class GeneratePdfJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        public string $html,
        public string $path,
        public ?string $disk = null,
        public array $options = [],
    ) {}

    public function handle(UnicodePdfManager $manager): void
    {
        $engine = isset($this->options['engine']) && is_string($this->options['engine'])
            ? $this->options['engine']
            : null;

        $document = $manager->createDocument($engine);

        if (isset($this->options['preset']) && is_string($this->options['preset'])) {
            $document->preset($this->options['preset']);
        }

        if (isset($this->options['font']) && is_string($this->options['font'])) {
            $document->font($this->options['font']);
        }

        if (isset($this->options['fallback']) && is_array($this->options['fallback'])) {
            $document->fallback($this->options['fallback']);
        }

        if (isset($this->options['direction']) && is_string($this->options['direction'])) {
            $document->direction($this->options['direction']);
        }

        if (isset($this->options['paper'])) {
            $document->setPaper(
                $this->options['paper'],
                is_string($this->options['orientation'] ?? null) ? $this->options['orientation'] : 'portrait'
            );
        }

        if (isset($this->options['metadata']) && is_array($this->options['metadata'])) {
            $document->metadata($this->options['metadata']);
        }

        $document->loadHtml($this->html)->store($this->path, $this->disk);
    }
}
