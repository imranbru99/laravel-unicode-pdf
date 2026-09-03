<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Tests\Unit;

use ImranDev\UnicodePdf\Services\HttpHeaderHelper;
use PHPUnit\Framework\TestCase;

class HttpHeaderHelperTest extends TestCase
{
    public function test_sanitizes_filename_and_appends_pdf(): void
    {
        $this->assertEquals('document.pdf', HttpHeaderHelper::sanitizeFilename(''));
        $this->assertEquals('invoice.pdf', HttpHeaderHelper::sanitizeFilename('invoice'));
        $this->assertEquals('invoice.pdf', HttpHeaderHelper::sanitizeFilename("invoice\r\n.pdf"));
        $this->assertEquals('বাংলা-রিপোর্ট.pdf', HttpHeaderHelper::sanitizeFilename('বাংলা-রিপোর্ট'));
    }

    public function test_generates_rfc_compliant_content_disposition(): void
    {
        $header = HttpHeaderHelper::makeContentDisposition('বাংলা-রিপোর্ট.pdf', 'attachment');

        $this->assertStringContainsString('attachment;', $header);
        $this->assertStringContainsString("filename*=UTF-8''", $header);
    }
}
