<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Tests\Unit;

use ImranDev\UnicodePdf\Native\FontLibrary;
use ImranDev\UnicodePdf\Native\HtmlLayouter;
use PHPUnit\Framework\TestCase;

class HtmlLayouterTest extends TestCase
{
    public function test_inline_font_size_overrides_heading_default(): void
    {
        $commands = $this->layout('<h1 style="font-size: 9pt;">Hello</h1>');
        $text = $this->firstText($commands);

        $this->assertEqualsWithDelta(9.0, $text['size'], 0.01);
    }

    public function test_stylesheet_class_sets_size_and_color(): void
    {
        $html = '<style>.price { font-size: 18pt; color: #ff0000; }</style><p class="price">Total</p>';
        $text = $this->firstText($this->layout($html));

        $this->assertEqualsWithDelta(18.0, $text['size'], 0.01);
        $this->assertEqualsWithDelta(1.0, $text['color'][0], 0.02);
        $this->assertEqualsWithDelta(0.0, $text['color'][1], 0.02);
    }

    public function test_utf8_html_is_not_decoded_as_latin1(): void
    {
        $layouter = new HtmlLayouter;
        $method = new \ReflectionMethod(HtmlLayouter::class, 'loadDom');
        $dom = $method->invoke($layouter, '<p>বাংলা हिन्दी مرحبا</p>');

        $this->assertStringContainsString('বাংলা', $dom->textContent);
        $this->assertStringContainsString('हिन्दी', $dom->textContent);
        $this->assertStringContainsString('مرحبا', $dom->textContent);
        $this->assertStringNotContainsString('à¦', $dom->textContent);
        $this->assertStringNotContainsString('à¤', $dom->textContent);
    }

    public function test_center_alignment_moves_text_away_from_left_margin(): void
    {
        $commands = $this->layout('<p style="text-align: center;">Hi</p>');
        $text = $this->firstText($commands);

        $this->assertGreaterThan(40.0, $text['x']);
    }

    public function test_page_break_before_starts_a_new_page(): void
    {
        $commands = $this->layout('<p>First</p><div style="page-break-before: always;"><p>Second</p></div>');
        $breaks = array_values(array_filter($commands, static fn (array $command): bool => ($command['type'] ?? '') === 'pagebreak'));

        $this->assertCount(1, $breaks);
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function layout(string $html): array
    {
        return (new HtmlLayouter)->layout(
            html: $html,
            fonts: new FontLibrary,
            pageWidth: 595.28,
            pageHeight: 841.89,
            marginLeft: 40,
            marginTop: 40,
            marginRight: 40,
            marginBottom: 40
        );
    }

    /**
     * @param  list<array<string, mixed>>  $commands
     * @return array<string, mixed>
     */
    protected function firstText(array $commands): array
    {
        foreach ($commands as $command) {
            if (($command['type'] ?? '') === 'text') {
                return $command;
            }
        }

        $this->fail('No text command was produced.');
    }
}
