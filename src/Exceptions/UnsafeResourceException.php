<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Exceptions;

class UnsafeResourceException extends UnicodePdfException
{
    public static function ssrfBlocked(string $url, string $reason = 'Private/restricted host or IP'): self
    {
        return new self("Remote resource access to \"{$url}\" was blocked by security policy: {$reason}.");
    }

    public static function pathTraversal(string $path): self
    {
        return new self("Path traversal or unauthorized file path access detected for path: \"{$path}\".");
    }

    public static function remoteDisabled(string $url): self
    {
        return new self("Remote resource fetching is disabled by default for \"{$url}\". Enable 'security.allow_remote_images' or 'security.allow_remote_fonts' in config/unicode-pdf.php if intentional.");
    }
}
