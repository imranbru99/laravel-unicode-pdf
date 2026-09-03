<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Services;

use ImranDev\UnicodePdf\Exceptions\UnsafeResourceException;

class ResourceResolver
{
    public function __construct(
        protected SecurityManager $securityManager = new SecurityManager
    ) {}

    /**
     * Convert an image path or URL into a base64 data URI safely.
     *
     * @throws UnsafeResourceException
     */
    public function toDataUri(string $pathOrUrl): string
    {
        if (str_starts_with($pathOrUrl, 'data:')) {
            return $pathOrUrl;
        }

        if (filter_var($pathOrUrl, FILTER_VALIDATE_URL)) {
            $this->securityManager->validateUrl($pathOrUrl, 'image');
            $content = @file_get_contents($pathOrUrl);
            if ($content === false) {
                throw new UnsafeResourceException("Unable to fetch remote image at \"{$pathOrUrl}\".");
            }
            $mime = 'image/png';
        } else {
            $safePath = $this->securityManager->validateLocalPath($pathOrUrl);
            if (! file_exists($safePath)) {
                throw new UnsafeResourceException("Local image file does not exist at \"{$pathOrUrl}\".");
            }

            $content = file_get_contents($safePath);
            $ext = strtolower(pathinfo($safePath, PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'jpg', 'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'svg' => 'image/svg+xml',
                'webp' => 'image/webp',
                default => 'image/png',
            };
        }

        return 'data:'.$mime.';base64,'.base64_encode($content);
    }
}
