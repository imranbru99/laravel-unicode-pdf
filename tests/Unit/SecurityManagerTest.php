<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Tests\Unit;

use ImranDev\UnicodePdf\Exceptions\UnsafeResourceException;
use ImranDev\UnicodePdf\Services\SecurityManager;
use PHPUnit\Framework\TestCase;

class SecurityManagerTest extends TestCase
{
    public function test_blocks_remote_resources_when_disabled(): void
    {
        $manager = new SecurityManager(allowRemoteImages: false);

        $this->expectException(UnsafeResourceException::class);
        $this->expectExceptionMessage('Remote resource fetching is disabled by default');

        $manager->validateUrl('https://example.com/image.png');
    }

    public function test_blocks_private_ip_and_metadata_when_remote_enabled(): void
    {
        $manager = new SecurityManager(allowRemoteImages: true);

        $this->expectException(UnsafeResourceException::class);
        $this->expectExceptionMessage('Resolves to private/loopback IP');

        $manager->validateUrl('http://127.0.0.1/secret.png');
    }

    public function test_blocks_path_traversal_with_null_byte(): void
    {
        $manager = new SecurityManager;

        $this->expectException(UnsafeResourceException::class);
        $this->expectExceptionMessage('Path traversal');

        $manager->validateLocalPath("/var/www/secret.pdf\0.jpg");
    }
}
