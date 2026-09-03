# Changelog

All notable changes to `laravel-unicode-pdf` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - 2026-09-01

### Added
- Built-in **native** PDF engine: generate Unicode PDFs with no Dompdf, mPDF, TCPDF, or Browsershot dependency.
- TrueType embedding (CIDFontType2 / Identity-H), HTML/table layout, RTL, Arabic joining, and Indic left-matra + GSUB ligature shaping.
- `php artisan unicode-pdf:font:install` now downloads Noto TTF files into the font directory.

### Changed
- Default engine is `native` (`UNICODE_PDF_ENGINE=native`). Third-party engines remain optional.

## [1.1.0] - 2026-09-01

### Added
- PHP 8.1+ enums for engines, direction, orientation, paper sizes, and presets.
- `Macroable`, `Conditionable`, and `Tappable` on documents; `Responsable` / `Stringable` HTTP + string conversion.
- Laravel testing fake: `UnicodePdf::fake()`, `assertGenerated()`, `assertDownloaded()`.
- Events: `PdfGenerating`, `PdfGenerated`, `PdfFailed`.
- Queued generation (`GeneratePdfJob`, `->queue()`), output cache (`->cache()`), and Laravel 11+ `->defer()`.
- Mail attachments (`toMailAttachment()`), `base64()` / `dataUri()`, controller `return $document`.
- Named config profiles (`UnicodePdf::profile('invoice')`) and `unicode_pdf()` helper.
- Native numeral conversion for Bengali, Arabic-Indic, Persian, Indic, Thai, Khmer, Myanmar, and more.
- Typography presets for Thai, Hebrew, Persian, Urdu, Tamil, Korean, Japanese, Vietnamese, Greek, Cyrillic, Ethiopic, Khmer, Myanmar, Sinhala, and Latin, plus locale aliases (`bn`, `ar`, `ja`, …).
- Broader Unicode script detection (Khmer, Myanmar, Lao, Tibetan, Adlam, NKo, Thaana, Javanese, emoji, …) and expanded RTL detection.
- Artisan `unicode-pdf:generate`, Blade `@unicodeNumerals`, `x-unicode-pdf::document` component, and `php artisan about` integration.
- `Pdfable` interface and `GeneratesPdf` model concern.
- CI for PHP 8.5 / Laravel 13, Pint + PHPStan quality job, Dependabot, SECURITY.md, `.gitattributes`, `.editorconfig`.

### Changed
- Unknown typography presets now throw `PresetNotFoundException` instead of failing silently.
- Document `save` / `store` / `download` / `stream` go through the same output pipeline so events and cache always apply.
- Removed invalid `Vendor\\UnicodePdf` Composer autoload alias.

### Fixed
- Capability checks now inspect the raw engine driver instead of allocating a throwaway document.

## [1.0.0] - 2026-09-01

### Added
- Core architecture with engine abstraction layer (`PdfEngine` contract).
- Adapters for **Dompdf**, **mPDF**, **TCPDF**, **Browsershot (Chromium)**, and zero-dependency **NullEngine**.
- Unicode processing subsystem (`Utf8Validator`, `UnicodeNormalizer`, `ScriptDetector`, `DirectionDetector`, `BidiProcessor`, `LocaleMapper`).
- Font management subsystem with binary TTF/OTF header parser (`FontMetadata`), dynamic font resolver (`FontResolver`), and CSS font-face builder (`FontCssHelper`).
- Typography presets for `bengali`, `arabic`, `indian`, `cjk`, and `universal`.
- Full Bengali first-class support (কার, যুক্তাক্ষর, ৳ মুদ্রা, সংখ্যা).
- Arabic and Hebrew Right-to-Left (RTL) and bidirectional mixed-script support.
- SSRF and Path Traversal security managers.
- RFC 5987 / RFC 6266 Unicode filename HTTP headers (`filename*=UTF-8''...`).
- Artisan commands: `unicode-pdf:diagnose`, `unicode-pdf:fonts`, `unicode-pdf:font:list`, `unicode-pdf:font:install`, `unicode-pdf:clear-cache`.
- Comprehensive Pest/PHPUnit test suite and multilingual Blade templates.
- Complete documentation suite.
