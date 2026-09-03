<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Services;

use ImranDev\UnicodePdf\Exceptions\UnsafeResourceException;

class SecurityManager
{
    /**
     * @param  array<string>  $allowedRemoteHosts
     * @param  array<string>  $allowedLocalPaths
     */
    public function __construct(
        protected bool $allowRemoteImages = false,
        protected bool $allowRemoteFonts = false,
        protected array $allowedRemoteHosts = [],
        protected array $allowedLocalPaths = []
    ) {}

    /**
     * Validate an image or asset URL against SSRF and host whitelists.
     *
     * @throws UnsafeResourceException
     */
    public function validateUrl(string $url, string $type = 'image'): void
    {
        $isImage = $type === 'image';
        $allowed = $isImage ? $this->allowRemoteImages : $this->allowRemoteFonts;

        if (! $allowed) {
            throw UnsafeResourceException::remoteDisabled($url);
        }

        $parsed = parse_url($url);
        if (! $parsed || ! isset($parsed['host']) || ! in_array(strtolower($parsed['scheme'] ?? ''), ['http', 'https'], true)) {
            throw UnsafeResourceException::ssrfBlocked($url, 'Invalid protocol or malformed URL');
        }

        $host = strtolower($parsed['host']);

        // Check if host is IP address or DNS resolves to private / loopback / link-local IP
        $ip = gethostbyname($host);

        if ($this->isPrivateIp($ip)) {
            throw UnsafeResourceException::ssrfBlocked($url, "Resolves to private/loopback IP ({$ip})");
        }

        if (! empty($this->allowedRemoteHosts) && ! in_array($host, $this->allowedRemoteHosts, true)) {
            throw UnsafeResourceException::ssrfBlocked($url, "Host '{$host}' is not in allowed hosts whitelist");
        }
    }

    /**
     * Validate local file path against directory traversal and permitted directories.
     *
     * @throws UnsafeResourceException
     */
    public function validateLocalPath(string $filePath): string
    {
        // Prevent null byte attacks before realpath
        if (str_contains($filePath, "\0")) {
            throw UnsafeResourceException::pathTraversal($filePath);
        }

        $real = realpath($filePath);
        if ($real === false) {
            // File might be a destination path for save() that doesn't exist yet
            $dir = dirname($filePath);
            $realDir = realpath($dir);
            if ($realDir === false) {
                throw UnsafeResourceException::pathTraversal($filePath);
            }
            $real = $realDir.DIRECTORY_SEPARATOR.basename($filePath);
        }

        if (! empty($this->allowedLocalPaths)) {
            $allowedPaths = array_merge($this->allowedLocalPaths, [
                sys_get_temp_dir(),
            ]);

            $matched = false;
            foreach ($allowedPaths as $allowedPath) {
                $allowedReal = realpath($allowedPath);
                if ($allowedReal && str_starts_with(strtolower($real), strtolower($allowedReal))) {
                    $matched = true;
                    break;
                }
            }

            if (! $matched) {
                throw UnsafeResourceException::pathTraversal($filePath);
            }
        }

        return $real;
    }

    /**
     * Check if an IP address belongs to private/loopback/cloud metadata ranges.
     */
    protected function isPrivateIp(string $ip): bool
    {
        // 127.0.0.0/8, 10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16, 169.254.0.0/16 (AWS / GCP metadata)
        if (
            filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            ) === false
        ) {
            return true;
        }

        // Cloud metadata protection (169.254.169.254)
        if ($ip === '169.254.169.254') {
            return true;
        }

        return false;
    }
}
