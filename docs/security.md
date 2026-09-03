# Security Subsystem

PDF rendering engines can be vulnerable to Server-Side Request Forgery (SSRF) and Local File Inclusion (LFI) when untrusted HTML is processed.

`laravel-unicode-pdf` implements proactive security defenses:

## 1. SSRF Protection
* Remote image and font fetching is **disabled by default**.
* Private IP ranges (`127.0.0.0/8`, `10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16`) and cloud metadata endpoints (`169.254.169.254`) are blocked.
* Optional domain whitelist via `security.allowed_remote_hosts`.

## 2. Directory Traversal Protection
* Path resolution verifies real paths and prevents null byte injection (`\0`).
* Destination save paths are confined to permitted directories (`base_path()`, `storage_path()`, `public_path()`).

## 3. Safe HTTP Headers
* Filenames in `Content-Disposition` headers are sanitized to block newline header injection.
* RFC 5987 / RFC 6266 `filename*=UTF-8''` encoding ensures non-ASCII filenames (e.g. `বাংলা-রিপোর্ট.pdf`, `تقرير.pdf`) download safely across all browsers.
