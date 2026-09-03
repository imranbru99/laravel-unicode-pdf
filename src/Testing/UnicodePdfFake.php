<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Testing;

use Illuminate\Support\Collection;
use ImranDev\UnicodePdf\UnicodePdfDocument;
use ImranDev\UnicodePdf\UnicodePdfManager;
use PHPUnit\Framework\Assert as PHPUnit;

class UnicodePdfFake extends UnicodePdfManager
{
    /**
     * @var array<int, array{document: UnicodePdfDocument, binary: string, filename: ?string, action: string}>
     */
    protected array $recorded = [];

    public function record(UnicodePdfDocument $document, string $binary, string $action = 'output', ?string $filename = null): void
    {
        $this->recorded[] = [
            'document' => $document,
            'binary' => $binary,
            'filename' => $filename,
            'action' => $action,
        ];
    }

    /**
     * @return Collection<int, array{document: UnicodePdfDocument, binary: string, filename: ?string, action: string}>
     */
    public function generated(): Collection
    {
        return collect($this->recorded);
    }

    public function assertGenerated(int $times = 1): self
    {
        $outputs = array_values(array_filter($this->recorded, fn (array $entry): bool => $entry['action'] === 'output'));

        PHPUnit::assertCount(
            $times,
            $outputs,
            "Expected {$times} PDF(s) to be generated, but ".count($outputs).' were.'
        );

        return $this;
    }

    public function assertNothingGenerated(): self
    {
        $outputs = array_values(array_filter($this->recorded, fn (array $entry): bool => $entry['action'] === 'output'));

        PHPUnit::assertEmpty(
            $outputs,
            'Expected no PDFs to be generated, but '.count($outputs).' were.'
        );

        return $this;
    }

    public function assertDownloaded(?string $filename = null): self
    {
        $matches = array_filter($this->recorded, function (array $entry) use ($filename): bool {
            if ($entry['action'] !== 'download') {
                return false;
            }

            return $filename === null || $entry['filename'] === $filename;
        });

        PHPUnit::assertNotEmpty(
            $matches,
            $filename === null
                ? 'Expected a PDF download, but none were recorded.'
                : "Expected a PDF download named [{$filename}], but none matched."
        );

        return $this;
    }

    public function assertStreamed(?string $filename = null): self
    {
        $matches = array_filter($this->recorded, function (array $entry) use ($filename): bool {
            if ($entry['action'] !== 'stream') {
                return false;
            }

            return $filename === null || $entry['filename'] === $filename;
        });

        PHPUnit::assertNotEmpty(
            $matches,
            $filename === null
                ? 'Expected a PDF stream, but none were recorded.'
                : "Expected a PDF stream named [{$filename}], but none matched."
        );

        return $this;
    }

    /**
     * @param  callable(UnicodePdfDocument, string): bool  $callback
     */
    public function assertGeneratedUsing(callable $callback): self
    {
        $found = false;

        foreach ($this->recorded as $entry) {
            if ($callback($entry['document'], $entry['binary'])) {
                $found = true;
                break;
            }
        }

        PHPUnit::assertTrue($found, 'Expected a generated PDF matching the given callback.');

        return $this;
    }
}
