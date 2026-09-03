# Laravel Unicode PDF

[![Latest Version on Packagist](https://img.shields.io/packagist/v/imrandevbd/laravel-unicode-pdf.svg?style=flat-square)](https://packagist.org/packages/imrandevbd/laravel-unicode-pdf)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/imranbru99/laravel-unicode-pdf/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/imranbru99/laravel-unicode-pdf/actions)
[![Total Downloads](https://img.shields.io/packagist/dt/imrandevbd/laravel-unicode-pdf.svg?style=flat-square)](https://packagist.org/packages/imrandevbd/laravel-unicode-pdf)
[![License](https://img.shields.io/packagist/l/imrandevbd/laravel-unicode-pdf.svg?style=flat-square)](https://github.com/imranbru99/laravel-unicode-pdf/blob/main/LICENSE)

Universal, multilingual, Unicode-first PDF generation for Laravel. Reliable RTL, complex-script shaping, intelligent font fallback, and a modern Laravel API (enums, events, queues, fakes, mail, cache).

```php
use ImranDev\UnicodePdf\Facades\UnicodePdf;

return UnicodePdf::preset('bengali')
    ->loadView('invoices.show', [
        'customer' => 'মোহাম্মদ ইমরান হোসেন',
        'total' => '৳৯০,০০০',
    ])
    ->a4()
    ->download('চালানপত্র.pdf');
```

---

## Table of contents

- [Requirements](#requirements)
- [Why this package](#why-this-package)
- [Feature overview](#feature-overview)
- [Installation](#installation)
- [Quick start](#quick-start)
- [Fluent document API](#fluent-document-api)
- [Typography presets](#typography-presets)
- [Locales](#locales)
- [Fonts](#fonts)
- [PDF engines](#pdf-engines)
- [Unicode processing](#unicode-processing)
- [Native numerals](#native-numerals)
- [HTTP responses](#http-responses)
- [Storage, queue, cache, mail](#storage-queue-cache-mail)
- [Events](#events)
- [Named profiles](#named-profiles)
- [Helpers, Blade, and components](#helpers-blade-and-components)
- [Models (`Pdfable` / `GeneratesPdf`)](#models-pdfable--generatespdf)
- [Custom engines](#custom-engines)
- [Enums](#enums)
- [Artisan commands](#artisan-commands)
- [Configuration](#configuration)
- [Security](#security)
- [Exceptions](#exceptions)
- [Testing](#testing)
- [Sample views](#sample-views)
- [Requirements matrix](#requirements-matrix)
- [Documentation](#documentation)
- [License](#license)

---

## Requirements

| | |
| :--- | :--- |
| PHP | `8.1` – `8.5` |
| Laravel | `10` – `13` |
| Extensions | `ext-mbstring`, `ext-json` (required) · `ext-intl`, `ext-gd` (recommended) |
| Engine packages | Optional — install the driver you use (see [PDF engines](#pdf-engines)) |

Auto-discovery registers `UnicodePdfServiceProvider` and the `UnicodePdf` facade. No extra setup is required in Laravel 10+.

---

## Why this package

Generating PDFs in non-Latin scripts often produces `???`, tofu boxes (`□`), broken Arabic ligatures, or disconnected Bengali conjuncts (`কৃষ্ণ`, `শিক্ষার্থী`).

This package sits **in front of** Dompdf, mPDF, TCPDF, or Chromium (Browsershot) and always:

1. Validates UTF-8
2. Detects Unicode scripts and text direction
3. Builds a font-fallback stack
4. Injects `@font-face` / `font-family` CSS and `dir` / `lang` on the HTML
5. Hands a prepared document to the engine you choose

You keep writing Blade. The package handles Unicode.

---

## Feature overview

| Area | What you get |
| :--- | :--- |
| **Scripts** | Bengali, Arabic, Urdu, Persian, Hebrew, Devanagari, Tamil, Telugu, Malayalam, Gujarati, Gurmukhi, Kannada, Sinhala, Thai, Lao, Khmer, Myanmar, Tibetan, Ethiopic, CJK (SC/TC/JP/KR), Greek, Cyrillic, Armenian, Georgian, Thaana, NKo, Adlam, Syriac, Javanese, Balinese, Sundanese, Yi, Cherokee, emoji, Latin |
| **RTL / BiDi** | Auto direction, mixed LTR+RTL, Hebrew / Arabic / Urdu / Persian / Thaana / NKo / Adlam / Syriac |
| **Fonts** | Register TTF/OTF, auto-discover directories, CSS `@font-face`, glyph diagnostics, Noto install guide |
| **Presets** | 20 typography presets + locale aliases (`bn`, `ar`, `ja`, …) |
| **Engines** | **Native (bundled, default)** — plus optional Dompdf, mPDF, TCPDF, Browsershot, Null, and `extend()` |
| **Laravel DX** | Facade, helper, enums, `Conditionable` / `Macroable` / `Tappable`, `Responsable`, `Stringable` |
| **Output** | `download`, `stream` / `inline`, `save`, `store` / `storeAs`, `output`, `base64`, `dataUri`, `toMailAttachment`, queue, cache, defer |
| **Document extras** | Watermark, encryption, metadata, headers/footers, page numbers, extra CSS, engine options |
| **Unicode tools** | UTF-8 validator, NFC/NFD/NFKC/NFKD normalizer, script detector, direction detector, BiDi processor, numeral converter |
| **Ops** | Artisan diagnose / fonts / generate / cache, `php artisan about`, events, testing fake |

---

## Installation

```bash
composer require imrandevbd/laravel-unicode-pdf
```

The **native** engine is built in. No Dompdf, mPDF, or other PDF library is required.

```bash
# Download Noto fonts for the scripts you use (SIL OFL)
php artisan unicode-pdf:font:install --font=bengali
php artisan unicode-pdf:font:install --font=arabic
php artisan unicode-pdf:font:install --font=universal
```

Optional third-party engines if you already use them: `dompdf/dompdf`, `mpdf/mpdf`, `tecnickcom/tcpdf`, `spatie/browsershot`.

Publish assets:

```bash
php artisan vendor:publish --tag=unicode-pdf-config
php artisan vendor:publish --tag=unicode-pdf-views
php artisan vendor:publish --tag=unicode-pdf-fonts
```

Optional Composer suggests:

```bash
# Mail attachments  → illuminate/mail (already in a full Laravel app)
# Queued PDFs       → illuminate/queue
# Output cache      → illuminate/cache
```

Set the default engine in `.env`:

```env
UNICODE_PDF_ENGINE=native
UNICODE_PDF_DEFAULT_FONT="Noto Sans"
```

---

## Quick start

### Download from a Blade view

```php
use ImranDev\UnicodePdf\Facades\UnicodePdf;

return UnicodePdf::loadView('invoices.show', [
    'customer' => 'মোহাম্মদ ইমরান হোসেন',
    'items' => [
        ['name' => 'ল্যাপটপ কম্পিউটার', 'price' => '৳৮৫,০০০'],
        ['name' => '4K مونيتر شاشة', 'price' => '١٬٥٠٠ ر.س'],
    ],
    'total' => '৳৯০,০০০',
])
->setPaper('a4')
->download('চালানপত্র.pdf');
```

### HTML, CSS, and font size

Developers style PDFs the same way as a Blade page: `<style>`, classes, inline styles, plus `->css()` and `->fontSize()`.

```php
return UnicodePdf::locale('bn')
    ->loadView('invoices.show', $data)
    ->fontSize(12)
    ->css('
        h1 { font-size: 22pt; color: #1a365d; text-align: center; }
        .total { font-size: 16pt; color: #c53030; font-weight: bold; }
        table th { background-color: #ebf8ff; }
    ')
    ->download('চালান.pdf');
```

```blade
<h1 style="font-size: 20pt; text-align: center;">বিক্রয় চালানপত্র</h1>
<p class="total" style="color: #2b6cb0;">মোট: ৳৯০,০০০</p>
```

Native layout & CSS engine supports:
- **Typography & Styling**: `font-size`, `font-family`, `font-weight` (bold), `font-style` (italic), `color`, `background-color`, `text-align` (`left`, `center`, `right`, `justify`), `text-decoration` (`underline`), `line-height`, `margin`, `padding`, `direction`.
- **Text Elements**: `<h1>`–`<h6>`, `<p>`, `<div>`, `<span>`, `<strong>`, `<b>`, `<em>`, `<i>`, `<u>`, `<a>`, `<br>`, `<hr>` (customizable width and color), `<blockquote>`.
- **Lists**: `<ul>`, `<ol>`, `<li>` (automatically rendered with bullet points `•`).
- **Tables**: `<table>`, `<thead>`, `<tbody>`, `<tr>`, `<th>`, `<td>` with automatic column width distribution, `colspan`, borders, cell padding, background color, cell-level font size/color/alignment, and auto-pagination across pages when table rows exceed page height.
- **Images**: `<img>` tags (local file paths, public directory assets, or `data:image/...;base64` URIs), auto-scaled with max-width or explicit `width`/`height`.
- **Page Breaks**: Force manual page breaks anywhere using `page-break-before: always`, `page-break-after: always`, `break-before: page`, `break-after: page`, or class `page-break`.

### Mixed-language HTML

```php
$html = '
    <h1>Universal Multilingual Document</h1>
    <p>English: Hello World</p>
    <p>বাংলা: শুভ সকাল, বাংলাদেশ (৳৮০,০০০)</p>
    <p>Arabic: مرحباً بالعالم (١٠٬٠٠٠ ر.س)</p>
    <p>Hindi: दुनिया में आपका स्वागत है</p>
    <p>Chinese: 世界你好</p>
';

return UnicodePdf::preset('universal')
    ->loadHtml($html)
    ->download('multilingual.pdf');
```

### Preview in the browser

```php
return UnicodePdf::loadView('reports.monthly', $data)
    ->stream('preview.pdf');
```

### Save locally or to S3

```php
UnicodePdf::loadView('invoices.show', $data)
    ->save(storage_path('app/invoices/inv-101.pdf'));

UnicodePdf::loadView('invoices.show', $data)
    ->store('invoices/inv-101.pdf', 's3');
```

### Return the document from a controller

`UnicodePdfDocument` implements Laravel’s `Responsable` interface:

```php
public function show(Invoice $invoice)
{
    return UnicodePdf::loadView('invoices.pdf', compact('invoice'))
        ->preset('bengali')
        ->name('চালান-'.$invoice->id.'.pdf');
}
```

### Helper

```php
return unicode_pdf('<h1>বাংলাদেশ</h1>')->preset('bengali')->download();

$manager = unicode_pdf(); // UnicodePdfManager
```

---

## Fluent document API

Every call returns the same `UnicodePdfDocument` instance (except terminal methods like `output()` / `download()`).

### Load content

| Method | Description |
| :--- | :--- |
| `loadHtml($html)` / `html($html)` | Raw HTML string |
| `loadView($view, $data = [], $mergeData = [])` / `view(...)` | Blade view |
| `loadFile($path)` | HTML file from disk |

### Typography & locale

| Method | Description |
| :--- | :--- |
| `font($family)` | Primary font family |
| `fontSize(14)` / `fontSize('16px')` | Default body size (appends CSS) |
| `fallback(array $fonts)` | Ordered fallback stack |
| `preset($name)` | Built-in preset or `Preset` enum (throws if unknown) |
| `locale($locale)` | Apply font + direction from locale map (`bn`, `ar`, `zh-TW`, …) |
| `direction('auto'\|'ltr'\|'rtl')` | Or `Direction` enum |
| `bidi(true)` | Enable bidirectional processing |
| `lang('bn')` | HTML `lang` attribute |
| `css($css)` | Extra CSS injected into the prepared HTML |

### Paper & layout

| Method | Description |
| :--- | :--- |
| `setPaper($size, $orientation)` / `paper(...)` | Size string, `[w, h]`, or `PaperSize` enum |
| `a4()` / `letter()` / `legal()` | Shortcuts |
| `orientation('portrait'\|'landscape')` | Or `Orientation` enum |
| `landscape()` / `portrait()` | Shortcuts |
| `setMargins($t, $r, $b, $l, $unit = 'mm')` / `margin(...)` | Page margins |
| `header($htmlOrView, $data = [])` | Header HTML or Blade view name |
| `footer($htmlOrView, $data = [])` | Footer HTML or Blade view name |
| `pageNumbers('{PAGE_NUM} / {PAGE_COUNT}')` | Page numbering format |

### Metadata & protection

| Method | Description |
| :--- | :--- |
| `metadata(['title' => '...', 'author' => '...'])` | PDF info dictionary |
| `title()` / `author()` / `subject()` / `keywords()` / `creator()` | Shortcuts |
| `watermark($text, $opacity = 0.2)` | Text watermark |
| `protect($options)` | Engine password / permissions array |
| `encrypt($userPassword, $ownerPassword = null, $permissions = [])` | Fluent wrapper around `protect()` |
| `options(array)` / `option($key, $value)` | Engine-specific options |
| `debug(true)` | Debug flag |
| `name('invoice.pdf')` | Default filename for download / `toResponse()` |

### Conditionable / macros / tap

```php
use ImranDev\UnicodePdf\Enums\Engine;
use ImranDev\UnicodePdf\Enums\PaperSize;
use ImranDev\UnicodePdf\Enums\Preset;

UnicodePdfDocument::macro('invoice', function () {
    return $this->preset(Preset::Bengali)->a4()->margin(15, 15, 15, 15);
});

return UnicodePdf::engine(Engine::Mpdf)
    ->invoice()
    ->paper(PaperSize::A4)
    ->when($isDraft, fn ($pdf) => $pdf->watermark('DRAFT'))
    ->unless($isPublic, fn ($pdf) => $pdf->encrypt('secret'))
    ->tap(fn ($pdf) => logger()->info('pdf', $pdf->snapshot()))
    ->loadView('invoices.show', $data)
    ->download();
```

---

## Typography presets

`preset()` sets default font, fallbacks, and direction in one call.

```php
UnicodePdf::preset('bengali');
UnicodePdf::preset(\ImranDev\UnicodePdf\Enums\Preset::Arabic);
```

Unknown names throw `PresetNotFoundException`.

### Built-in presets

| Preset | Direction | Default font | Typical use |
| :--- | :--- | :--- | :--- |
| `bengali` | LTR | Noto Sans Bengali | Bangla (কার, যুক্তাক্ষর, ৳) |
| `arabic` | RTL | Noto Sans Arabic | Arabic, RTL + BiDi |
| `indian` | LTR | Noto Sans Devanagari | Hindi, Marathi, Nepali + other Indic fallbacks |
| `cjk` | LTR | Noto Sans CJK SC | Chinese / Japanese / Korean stack |
| `universal` | auto | Noto Sans | Mixed-script documents |
| `thai` | LTR | Noto Sans Thai | Thai |
| `hebrew` | RTL | Noto Sans Hebrew | Hebrew |
| `persian` | RTL | Noto Sans Arabic | Farsi |
| `urdu` | RTL | Noto Nastaliq Urdu | Urdu |
| `tamil` | LTR | Noto Sans Tamil | Tamil |
| `korean` | LTR | Noto Sans CJK KR | Hangul |
| `japanese` | LTR | Noto Sans CJK JP | Kanji / Kana |
| `vietnamese` | LTR | Noto Sans | Vietnamese Latin |
| `greek` | LTR | Noto Sans | Greek |
| `cyrillic` | LTR | Noto Sans | Russian, Ukrainian, … |
| `ethiopic` | LTR | Noto Sans Ethiopic | Amharic, Tigrinya |
| `khmer` | LTR | Noto Sans Khmer | Khmer |
| `myanmar` | LTR | Noto Sans Myanmar | Burmese |
| `sinhala` | LTR | Noto Sans Sinhala | Sinhala |
| `latin` | LTR | Noto Sans | European Latin |

### Locale aliases for presets

These resolve to the same presets:

`bn`, `hi`, `zh`, `fa`, `ur`, `ja`, `ko`, `he`, `th`, `el`, `ru`, `am`, `km`, `my`, `si`, `ta`, `vi`, `en`

```php
UnicodePdf::preset('bn');   // same as 'bengali'
UnicodePdf::preset('ja');   // same as 'japanese'
```

Register your own:

```php
use ImranDev\UnicodePdf\Fonts\Presets\ScriptPreset;
use ImranDev\UnicodePdf\Facades\UnicodePdf;

UnicodePdf::getFontManager()->registerPreset(new ScriptPreset(
    name: 'odia',
    defaultFont: 'Noto Sans Oriya',
    fallbackFonts: ['Noto Sans Oriya', 'Noto Sans'],
    direction: 'ltr',
    options: ['script' => 'Odia', 'complex_shaping' => true],
));
```

---

## Locales

`locale('ar')` sets font and direction from the built-in map.

```php
UnicodePdf::locale('bn')->loadView('invoices.show', $data)->download();
UnicodePdf::locale('zh-TW'); // Traditional Chinese font
UnicodePdf::locale('fa');    // RTL + Noto Sans Arabic
```

### Built-in locale codes

| Locale | Script | Dir | Font |
| :--- | :--- | :---: | :--- |
| `bn`, `as` | Bengali | LTR | Noto Sans Bengali |
| `ar`, `ps`, `sd` | Arabic | RTL | Noto Sans Arabic |
| `ur` | Arabic | RTL | Noto Nastaliq Urdu |
| `fa` | Arabic | RTL | Noto Sans Arabic |
| `he`, `yi` | Hebrew | RTL | Noto Sans Hebrew |
| `dv` | Thaana | RTL | Noto Sans Thaana |
| `hi`, `mr`, `ne` | Devanagari | LTR | Noto Sans Devanagari |
| `ta` `te` `ml` `gu` `pa` `kn` `or` `si` | Indic | LTR | matching Noto Sans * |
| `th` `lo` `km` `my` `bo` | SEA / Tibetan | LTR | matching Noto Sans * |
| `am`, `ti` | Ethiopic | LTR | Noto Sans Ethiopic |
| `zh`, `zh-CN` | CJK | LTR | Noto Sans CJK SC |
| `zh-TW`, `zh-HK` | CJK | LTR | Noto Sans CJK TC |
| `ja` | Japanese | LTR | Noto Sans CJK JP |
| `ko` | Korean | LTR | Noto Sans CJK KR |
| `ru` `uk` `bg` `sr` | Cyrillic | LTR | Noto Sans |
| `el` | Greek | LTR | Noto Sans |
| `hy` | Armenian | LTR | Noto Sans Armenian |
| `ka` | Georgian | LTR | Noto Sans Georgian |
| `jv` | Javanese | LTR | Noto Sans Javanese |
| `en` `es` `fr` `de` `pt` `tr` `id` `ms` `vi` | Latin | LTR | Noto Sans |

Override or add mappings:

```php
use ImranDev\UnicodePdf\Unicode\LocaleMapper;

LocaleMapper::register('ckb', 'Arabic', 'rtl', 'Noto Sans Arabic');
```

---

## Fonts

### Register a family

```php
UnicodePdf::registerFont([
    'family' => 'Kalpurush',
    'regular' => storage_path('app/unicode-pdf/fonts/kalpurush.ttf'),
    'bold' => storage_path('app/unicode-pdf/fonts/kalpurush-bold.ttf'),
    'italic' => storage_path('app/unicode-pdf/fonts/kalpurush-italic.ttf'),
    'bold_italic' => storage_path('app/unicode-pdf/fonts/kalpurush-bold-italic.ttf'),
]);

return UnicodePdf::font('Kalpurush')
    ->loadView('invoice', $data)
    ->download('invoice.pdf');
```

Same structure is accepted in `config/unicode-pdf.php` → `fonts`.

Supported files: **TTF**, **OTF** (all engines) · **WOFF / WOFF2** (Browsershot).

### Discover a directory

```php
$discovered = UnicodePdf::getFontManager()->discoverDirectory(
    storage_path('app/unicode-pdf/fonts')
);
```

Automatically scans the directory for `.ttf` and `.otf` fonts, parses their font headers, extracts font family names, styles (`regular`, `bold`, `italic`, `bold_italic`), and supported Unicode scripts, then registers them into the active font manager. Returns an associative array of discovered families.

### Inspect font metadata

```php
use ImranDev\UnicodePdf\Fonts\FontMetadata;

$metadata = FontMetadata::parse(storage_path('app/unicode-pdf/fonts/NotoSansBengali-Regular.ttf'));
// [
//     'family'            => 'Noto Sans Bengali',
//     'subfamily'         => 'Regular',
//     'full_name'         => 'Noto Sans Bengali Regular',
//     'postscript_name'   => 'NotoSansBengali-Regular',
//     'format'            => 'TTF', // 'TTF' | 'OTF' | 'WOFF' | 'WOFF2'
//     'num_glyphs'        => 745,
//     'unicode_ranges'    => [...],
//     'supported_scripts' => ['Bengali', 'Latin'],
// ]
```

### Glyph & script coverage diagnostics

Diagnose text against any font family to identify missing scripts and get suggested fonts:

```php
$report = UnicodePdf::checkGlyphs('বাংলা ও English ও مرحبا', 'Noto Sans');

// $report returns:
// [
//     'detected_scripts' => ['Bengali' => 6, 'Latin' => 7, 'Arabic' => 5],
//     'dominant_script'  => 'Bengali',
//     'primary_font'     => 'Noto Sans',
//     'missing_scripts'  => ['Bengali', 'Arabic'],
//     'suggested_fonts'  => ['AI-Borno', 'Noto Sans Arabic'],
// ]
```

### Install Noto fonts (guide)

Install pre-configured Google Noto fonts directly via Artisan:

```bash
# Interactive font installer menu:
php artisan unicode-pdf:font:install

# Direct installation:
php artisan unicode-pdf:font:install --font=bengali

# Force re-download even if already downloaded:
php artisan unicode-pdf:font:install --font=bengali --force
```

Available `--font` values: `bengali`, `arabic`, `devanagari`, `tamil`, `thai`, `hebrew`, `khmer`, `myanmar`, `ethiopic`, `universal`.

---

## PDF engines

```php
UnicodePdf::engine('mpdf')->preset('bengali')->loadView(...)->download();
UnicodePdf::engine(\ImranDev\UnicodePdf\Enums\Engine::Browsershot);
```

`native` is the default, zero-dependency engine (TTF embedding, RTL, Arabic joining, Indic GSUB). `null` writes a minimal PDF 1.4 binary for tests.

### Capability matrix

| | Package | Unicode | RTL | Complex shaping | SVG | JS | Encryption | Attachments | Best for |
| :--- | :--- | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :--- |
| **Native** | *(bundled)* | ✓ | ✓ | ✓ | — | — | — | — | Default — no extra packages |
| **Dompdf** | `dompdf/dompdf` | ✓ | — | — | ✓ | — | ✓ | — | Optional |
| **mPDF** | `mpdf/mpdf` | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | Optional |
| **TCPDF** | `tecnickcom/tcpdf` | ✓ | ✓ | — | ✓ | — | ✓ | ✓ | Optional |
| **Browsershot** | `spatie/browsershot` | ✓ | ✓ | ✓ (HarfBuzz) | ✓ | ✓ | — | — | Optional |
| **Null** | (bundled) | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ | Tests / CI |

```php
if (UnicodePdf::supports('font-shaping')) {
    // default engine
}

if (UnicodePdf::engine('mpdf')->supports('rtl')) {
    // ...
}
```

Capability names: `unicode`, `rtl`, `font-shaping`, `svg`, `javascript`, `encryption`, `attachments`.

---

## Unicode processing

Used automatically when HTML is prepared; also available on the manager and container contracts.

```php
// UTF-8 Validation
UnicodePdf::validateUtf8($text);                 // bool (throws InvalidUtf8Exception if invalid)

// Unicode Normalization (NFC, NFD, NFKC, NFKD)
UnicodePdf::normalize($text, 'NFC');

// Script Detection
UnicodePdf::detectScripts('বাংলা Hello مرحبا'); // ['Bengali' => 5, 'Latin' => 5, 'Arabic' => 5]

// Text Direction Detection ('ltr' or 'rtl')
UnicodePdf::detectDirection($html);              // 'ltr' | 'rtl'

// Glyph & Script Diagnostics
$report = UnicodePdf::checkGlyphs($html, 'Noto Sans');
```

### Script & direction utilities

```php
use ImranDev\UnicodePdf\Contracts\ScriptDetectorInterface;
use ImranDev\UnicodePdf\Contracts\DirectionDetectorInterface;

$scriptDetector = app(ScriptDetectorInterface::class);
$scriptDetector->getDominantScript($text);          // 'Bengali'
$scriptDetector->containsScript($text, 'Arabic');    // bool
$scriptDetector->containsComplexScript($text);      // bool (true if font shaping is required)

$dirDetector = app(DirectionDetectorInterface::class);
$dirDetector->detect($text);                         // 'ltr' | 'rtl'
$dirDetector->isRtl($text);                          // bool
$dirDetector->isMixed($text);                        // bool (both RTL and LTR detected)
```

### Detected scripts

Bengali, Arabic, Devanagari, Tamil, Telugu, Malayalam, Gujarati, Gurmukhi, Kannada, Sinhala, Thai, Hebrew, Cyrillic, Greek, Japanese, Korean, CJK, Armenian, Georgian, Ethiopic, Khmer, Myanmar, Lao, Tibetan, Mongolian, Thaana, Nko, Adlam, Syriac, Javanese, Balinese, Sundanese, Yi, Cherokee, Emoji, Latin.

Complex-script detection (needs shaping): Bengali, Arabic, Devanagari, Tamil, Telugu, Malayalam, Gujarati, Gurmukhi, Kannada, Sinhala, Thai, Khmer, Myanmar, Lao, Tibetan, Javanese, Balinese, Sundanese, Thaana, Nko, Adlam, Syriac.

RTL detection includes Hebrew, Arabic, Syriac, Thaana, NKo, Samaritan, Mandaic, Adlam, and presentation forms. Mixed LTR+RTL is detected for BiDi.

Register extra script regexes:

```php
app(\ImranDev\UnicodePdf\Contracts\ScriptDetectorInterface::class)
    ->registerScript('Odia', '/[\x{0B00}-\x{0B7F}]/u');
```

---

## Native numerals

Convert Western digits to native digits (and back).

```php
use ImranDev\UnicodePdf\Support\NumeralConverter;

NumeralConverter::toBengali('85000');          // ৮৫০০০
NumeralConverter::toArabicIndic('12345');      // ١٢٣٤٥
NumeralConverter::toPersian('12345');          // ۱۲۳۴۵  (۴/۵/۶ differ from Arabic-Indic)
NumeralConverter::toDevanagari('123');         // १२३
NumeralConverter::convert('123', 'th');        // ๑๒๓
NumeralConverter::format(85000, 'bn');         // ৮৫,০০০
NumeralConverter::toLatin('৮৫০০০', 'bn');     // 85000

UnicodePdf::numerals('85000', 'bn');           // ৮৫০০০
```

Blade:

```blade
৳ @unicodeNumerals('85000', 'bn')
```

| Locale / alias | Digits |
| :--- | :--- |
| `bn` / `bengali` / `bangla` | ০১২৩৪৫৬৭৮৯ |
| `ar` / `arabic` / `ur` / `urdu` | ٠١٢٣٤٥٦٧٨٩ |
| `fa` / `persian` / `farsi` | ۰۱۲۳۴۵۶۷۸۹ |
| `hi` / `devanagari` / `hindi` / `mr` / `ne` | ०१२३४५६७८९ |
| `ta` `te` `ml` `gu` `pa` `kn` `si` | Tamil, Telugu, Malayalam, Gujarati, Gurmukhi, Kannada, Sinhala |
| `th` `lo` `my` `km` | Thai, Lao, Myanmar, Khmer |
| `en` / `latin` / `western` | 0123456789 (no change) |

---

## HTTP responses

Unicode filenames use RFC 5987 / RFC 6266 (`filename="ascii.pdf"; filename*=UTF-8''...`). CR/LF/NUL are stripped.

| Method | `Content-Disposition` |
| :--- | :--- |
| `download('চালান.pdf')` | `attachment` |
| `stream('preview.pdf')` | `inline` |
| `inline('preview.pdf')` | `inline` (also used if you later `toResponse()`) |
| `toResponse($request)` | download unless `inline()` was called |
| `(string) $document` | raw PDF binary (`Stringable`) |

```php
$response = UnicodePdf::loadHtml('<p>Hi</p>')->download('বাংলা.pdf');
// Content-Type: application/pdf
// Content-Disposition: attachment; filename="______.pdf"; filename*=UTF-8''%E0%A6%AC%E0%A6%BE%E0%A6%82%E0%A6%B2%E0%A6%BE.pdf
```

Response macro:

```php
return response()->unicodePdf($document, 'invoice.pdf');
```

---

## Storage, queue, cache, mail

### Filesystem

```php
$pdf->save(storage_path('app/invoices/101.pdf'));     // local path (SSRF/path-safe)
$pdf->store('invoices/101.pdf', 's3');                // Laravel disk
$pdf->storeAs('invoices', '101.pdf', 's3');
```

### Binary helpers

```php
$binary = $pdf->output();
$b64    = $pdf->base64();
$uri    = $pdf->dataUri(); // data:application/pdf;base64,...
```

### Queue

Requires `illuminate/queue`. Dispatches `GeneratePdfJob`:

```php
UnicodePdf::loadView('invoices.show', $data)
    ->preset('bengali')
    ->queue('invoices/101.pdf', 's3');
```

Config: `unicode-pdf.queue.connection`, `unicode-pdf.queue.queue`.

### Cache rendered binaries

```php
UnicodePdf::loadView('invoices.show', $data)
    ->cache(3600)              // seconds; store from config if omitted
    ->cache(3600, 'redis')
    ->withoutCache()
    ->download('invoice.pdf');
```

Config: `unicode-pdf.cache.ttl`, `unicode-pdf.cache.store`.

### Defer after the HTTP response (Laravel 11+)

```php
UnicodePdf::loadView('invoices.show', $data)
    ->defer('invoices/101.pdf', 'local');
```

Falls back to synchronous `store()` when `defer()` is not available.

### Mail attachments

Requires `illuminate/mail`:

```php
Mail::to($user)->send(new InvoiceMailable(
    UnicodePdf::loadView('invoices.show', $data)
        ->preset('bengali')
        ->toMailAttachment('চালান.pdf')
));
```

---

## Events

Dispatched around `output()` (and therefore around download / stream / save / store):

| Event | Payload |
| :--- | :--- |
| `ImranDev\UnicodePdf\Events\PdfGenerating` | `document` |
| `ImranDev\UnicodePdf\Events\PdfGenerated` | `document`, `binary`, `durationMs`, `size()` |
| `ImranDev\UnicodePdf\Events\PdfFailed` | `document`, `exception` |

```php
use ImranDev\UnicodePdf\Events\PdfGenerated;

Event::listen(PdfGenerated::class, function (PdfGenerated $event) {
    logger()->info('pdf.generated', [
        'bytes' => $event->size(),
        'ms' => $event->durationMs,
    ]);
});
```

---

## Named profiles

Define reusable setups in `config/unicode-pdf.php`:

```php
'profiles' => [
    'invoice' => [
        'engine' => 'mpdf',
        'preset' => 'bengali',
        'paper' => 'a4',
        'orientation' => 'portrait',
        'locale' => 'bn',
        'font' => 'Noto Sans Bengali',
        'fallback' => ['Noto Sans Bengali', 'Noto Sans'],
        'direction' => 'ltr',
        'metadata' => ['creator' => 'Acme ERP'],
    ],
],
```

```php
return UnicodePdf::profile('invoice')
    ->loadView('invoices.show', $data)
    ->download('invoice.pdf');
```

Bundled examples: `invoice`, `receipt`, `rtl-report`. Missing names throw `PresetNotFoundException::profile()`.

---

## Helpers, Blade, and components

### `unicode_pdf()`

```php
unicode_pdf();                         // manager
unicode_pdf('<h1>Hello</h1>');         // document with HTML loaded
```

### `@unicodeNumerals`

```blade
@unicodeNumerals('85000', 'bn')
```

### `<x-unicode-pdf::document>`

UTF-8 HTML shell with `dir` / `lang`:

```blade
<x-unicode-pdf::document lang="bn" dir="ltr" title="চালান">
    <h1>{{ $customer }}</h1>
    <p>মোট: ৳{{ $total }}</p>
</x-unicode-pdf::document>
```

Published views live under `resources/views/vendor/unicode-pdf/`.

Package views (namespace `unicode-pdf::`):

- `unicode-pdf::sample-multilingual`
- `unicode-pdf::story-all-languages`
- `unicode-pdf::invoice-bengali`
- `unicode-pdf::invoice-arabic`
- `unicode-pdf::invoice-hindi`
- `unicode-pdf::components.document`

`php artisan about` includes **Unicode PDF** (engine, default font, direction).

---

## Models (`Pdfable` / `GeneratesPdf`)

```php
use ImranDev\UnicodePdf\Concerns\GeneratesPdf;
use ImranDev\UnicodePdf\Concerns\Pdfable;

class Invoice extends Model implements Pdfable
{
    use GeneratesPdf;

    protected function pdfView(): string
    {
        return 'invoices.pdf';
    }

    protected function pdfData(): array
    {
        return ['invoice' => $this];
    }

    protected function pdfPreset(): string
    {
        return 'bengali';
    }

    protected function pdfFilename(): string
    {
        return 'invoice-'.$this->id.'.pdf';
    }
}

return $invoice->toPdf()->download();
```

---

## Custom engines

```php
use ImranDev\UnicodePdf\Engines\AbstractPdfEngine;

UnicodePdf::extend('gotenberg', function ($app, $fontManager, $securityManager) {
    return new class($app['view'] ?? null, $fontManager, $securityManager) extends AbstractPdfEngine {
        public function getName(): string
        {
            return 'gotenberg';
        }

        public function supports(string $capability): bool
        {
            return true;
        }

        public function output(): string
        {
            // Call your renderer with $this->getPreparedContent()
            return '%PDF-1.4 ...';
        }
    };
});

return UnicodePdf::engine('gotenberg')->loadHtml($html)->download();
```

Implement `ImranDev\UnicodePdf\Contracts\PdfEngine` if you do not extend `AbstractPdfEngine`.

---

## Enums

```php
use ImranDev\UnicodePdf\Enums\Direction;
use ImranDev\UnicodePdf\Enums\Engine;
use ImranDev\UnicodePdf\Enums\Orientation;
use ImranDev\UnicodePdf\Enums\PaperSize;
use ImranDev\UnicodePdf\Enums\Preset;

UnicodePdf::engine(Engine::Mpdf)
    ->preset(Preset::Bengali)
    ->paper(PaperSize::A4, Orientation::Portrait)
    ->direction(Direction::Ltr);
```

| Enum | Cases |
| :--- | :--- |
| `Engine` | `Native`, `Dompdf`, `Mpdf`, `Tcpdf`, `Browsershot`, `Null` — `label()`, `composerPackage()`, `values()` |
| `Direction` | `Auto`, `Ltr`, `Rtl` |
| `Orientation` | `Portrait`, `Landscape` — `short()` → `P` / `L` |
| `PaperSize` | `A3` `A4` `A5` `A6` `Letter` `Legal` `Tabloid` `Executive` `Folio` `B4` `B5` — `dimensions()` in points |
| `Preset` | All preset names listed above — `values()` |

String names still work everywhere enums are accepted.

---

## Artisan commands

| Command | Purpose |
| :--- | :--- |
| `php artisan unicode-pdf:diagnose` | Comprehensive system check: PHP version, extensions, engine adapters, font search paths, capabilities |
| `php artisan unicode-pdf:fonts` | Displays default, registered, and fallback font coverage across scripts |
| `php artisan unicode-pdf:font:list` | Formatted table of all registered font families and their variant file paths |
| `php artisan unicode-pdf:font:install` | Download SIL OFL Google Noto fonts (interactive or via `--font` flag) |
| `php artisan unicode-pdf:clear-cache` | Clears cached font metadata, glyph tables, and temporary PDF files |
| `php artisan unicode-pdf:generate` | CLI PDF compiler from a Blade view or raw HTML |

### Font installation

```bash
# Interactive menu prompt:
php artisan unicode-pdf:font:install

# Targeted font install:
php artisan unicode-pdf:font:install --font=bengali

# Force overwrite if already downloaded:
php artisan unicode-pdf:font:install --font=arabic --force
```

### CLI generation

```bash
# From a Blade view:
php artisan unicode-pdf:generate invoices.show \
    --output=storage/app/invoice.pdf \
    --preset=bengali \
    --engine=native \
    --paper=a4 \
    --orientation=portrait

# From a raw HTML string:
php artisan unicode-pdf:generate \
    --html="<h1>বাংলাদেশ</h1>" \
    --output=/tmp/out.pdf \
    --preset=bengali \
    --filename=bangladesh.pdf
```

Options for `unicode-pdf:generate`:
- `view`: Blade view template name
- `--html`: Raw HTML content string
- `--output`: Destination path on disk (defaults to `storage/app/unicode-pdf/generated/{filename}`)
- `--preset`: Preset name (`bengali`, `arabic`, `devanagari`, `cjk`, `universal`, etc.)
- `--engine`: PDF engine (`native`, `dompdf`, `mpdf`, `tcpdf`, `browsershot`, `null`)
- `--paper`: Paper size (default: `a4`)
- `--orientation`: `portrait` (default) or `landscape`
- `--filename`: Filename for downloaded / saved artifact (default: `document.pdf`)

---

## Configuration

Published file: `config/unicode-pdf.php`.

| Key | Default | Meaning |
| :--- | :--- | :--- |
| `engine` | `env('UNICODE_PDF_ENGINE', 'native')` | Default driver (built-in) |
| `default_font` | `Noto Sans` | Primary family |
| `fallback_fonts` | Noto multi-script list | Fallback chain |
| `fonts` | `[]` | Registered TTF/OTF paths |
| `font_path` | `storage/app/unicode-pdf/fonts` | Font storage |
| `font_cache` | `storage/app/unicode-pdf/cache/fonts` | Parsed font cache |
| `font_detection.enabled` | `true` | Script analysis |
| `font_detection.auto_inject_css` | `true` | Inject `@font-face` + `font-family` |
| `font_detection.dominant_script_only` | `false` | Limit detection to dominant script |
| `direction` | `auto` | `auto` / `ltr` / `rtl` |
| `bidi` | `true` | Bidirectional assistance |
| `normalization.enabled` | `false` | Unicode normalize HTML |
| `normalization.form` | `NFC` | `NFC` `NFD` `NFKC` `NFKD` |
| `paper` | `a4` | Default paper |
| `orientation` | `portrait` | Default orientation |
| `margins` | `10mm` all sides | Default margins |
| `emoji.enabled` | `true` | Emoji handling |
| `emoji.fallback_font` | `Noto Color Emoji` | Emoji fallback |
| `security.allow_remote_images` | `false` | Remote `<img>` |
| `security.allow_remote_fonts` | `false` | Remote fonts |
| `security.allowed_remote_hosts` | `[]` | Host allowlist when remote is on |
| `security.allowed_local_paths` | `base`, `storage`, `public` | Path allowlist |
| `performance.cache_enabled` | `true` | Font/script cache files |
| `performance.cache_path` | `storage/app/unicode-pdf/cache` | Cache dir |
| `performance.lazy_font_loading` | `true` | Load fonts on demand |
| `logging.enabled` | `false` | Diagnostic logs |
| `logging.channel` | `stack` | Log channel |
| `cache.store` / `cache.ttl` | `null` / `3600` | Laravel cache for PDF binaries |
| `queue.connection` / `queue.queue` | `null` / `default` | `GeneratePdfJob` |
| `profiles` | `invoice`, `receipt`, `rtl-report` | Named document profiles |

Environment variables: `UNICODE_PDF_ENGINE`, `UNICODE_PDF_DEFAULT_FONT`, `UNICODE_PDF_LOGGING`, `UNICODE_PDF_LOG_CHANNEL`, `UNICODE_PDF_CACHE_STORE`, `UNICODE_PDF_CACHE_TTL`, `UNICODE_PDF_QUEUE_CONNECTION`, `UNICODE_PDF_QUEUE`.

---

## Security

Enabled by default:

| Control | Behavior |
| :--- | :--- |
| **SSRF** | Remote images/fonts off unless you opt in. Private/reserved IPs and `169.254.169.254` (cloud metadata) are blocked. Optional host allowlist. |
| **Path traversal** | `realpath` + allowlisted roots; NUL-byte rejected. Temp dir is always allowed for generated files. |
| **Header injection** | Filenames cannot contain CR/LF/NUL. UTF-8 names encoded as `filename*=UTF-8''...`. |

Enable remote assets only with a tight allowlist:

```php
'security' => [
    'allow_remote_images' => true,
    'allowed_remote_hosts' => ['cdn.example.com'],
],
```

`ResourceResolver::toDataUri()` converts local/remote images to base64 data URIs after the same checks.

---

## Exceptions

All extend `ImranDev\UnicodePdf\Exceptions\UnicodePdfException`:

| Exception | When |
| :--- | :--- |
| `InvalidUtf8Exception` | Input is not valid UTF-8 (offset + snippet) |
| `UnsupportedEngineException` | Unknown engine or missing Composer package |
| `PresetNotFoundException` | Unknown preset or profile |
| `FontRegistrationException` | Font definition missing `family` |
| `FontNotFoundException` | Requested family not registered |
| `MissingGlyphException` | Glyph coverage failure |
| `UnsupportedScriptException` | Script not supported by the current setup |
| `UnsafeResourceException` | SSRF or path traversal |
| `PdfGenerationException` | Engine failed to render / view factory missing |

---

## Testing

```bash
composer test
composer test:unit
composer test:feature
composer test:coverage
composer lint
composer analyse
composer check          # pint --test + phpstan + pest
```

The test suite uses the **null** engine (`UNICODE_PDF_ENGINE=null`). Dompdf / mPDF integration tests skip when those packages are not installed.

### Fake (like `Mail::fake()`)

```php
use ImranDev\UnicodePdf\Facades\UnicodePdf;

UnicodePdf::fake();

$this->get('/invoices/101/pdf')->assertOk();

UnicodePdf::assertGenerated();
UnicodePdf::assertGenerated(2);
UnicodePdf::assertNothingGenerated();
UnicodePdf::assertDownloaded('invoice.pdf');
UnicodePdf::assertStreamed('preview.pdf');
UnicodePdf::assertGeneratedUsing(function ($document, $binary) {
    return $document->getPresetName() === 'bengali';
});

$all = UnicodePdf::generated(); // Illuminate\Support\Collection
```

`Event::fake()` and `Queue::fake()` work with `PdfGenerated` / `GeneratePdfJob` as usual.

---

## Sample views

After publishing `--tag=unicode-pdf-views`:

```php
return UnicodePdf::preset('bengali')
    ->loadView('unicode-pdf::invoice-bengali', $data)
    ->download('invoice.pdf');
```

| View | Language |
| :--- | :--- |
| `unicode-pdf::invoice-bengali` | Bangla invoice |
| `unicode-pdf::invoice-arabic` | Arabic RTL invoice |
| `unicode-pdf::invoice-hindi` | Hindi / Devanagari |
| `unicode-pdf::sample-multilingual` | Mixed English + Bengali + Arabic + Hindi + CJK |
| `unicode-pdf::story-all-languages` | Same full-page story in every mapped language |

---

## Requirements matrix

CI runs on PHP **8.1–8.5** and Laravel **10–13** (incompatible pairs excluded, e.g. PHP 8.1 × Laravel 11+).

| Laravel | PHP | Testbench |
| :--- | :--- | :--- |
| 10 | 8.1 – 8.5 | 8 |
| 11 | 8.2 – 8.5 | 9 |
| 12 | 8.2 – 8.5 | 10 |
| 13 | 8.3 – 8.5 | 11 |

---

## Manager reference

Resolved as `unicode-pdf` / `UnicodePdfManager`. The facade proxies here; unknown methods are forwarded to a new document.

| Method | Returns |
| :--- | :--- |
| `createDocument($engine = null)` | `UnicodePdfDocument` |
| `engine($name)` | document using that engine |
| `driver($name)` | raw `PdfEngine` |
| `profile($name)` | document from config profile |
| `extend($driver, Closure)` | register custom engine |
| `getDefaultEngine()` | string |
| `getFontManager()` | `FontManager` |
| `registerFont(array)` | void |
| `fake()` | `UnicodePdfFake` |
| `supports($capability)` | bool |
| `validateUtf8` / `normalize` / `numerals` | Unicode helpers |
| `detectScripts` / `detectDirection` / `checkGlyphs` | analysis |
| `clearCache()` | bool |
| `when` / `unless` / `tap` / macros | Laravel traits |

---

## Documentation

| Guide | |
| :--- | :--- |
| [Installation](docs/installation.md) | Requirements, engines, publish tags |
| [Quickstart](docs/quickstart.md) | Copy-paste examples |
| [Configuration](docs/configuration.md) | `config/unicode-pdf.php` |
| [Fonts](docs/fonts.md) | Registration, presets, discovery |
| [Unicode](docs/unicode.md) | Validation, normalization, scripts |
| [Bengali](docs/bengali.md) | কার, যুক্তাক্ষর, ৳ |
| [Arabic](docs/arabic.md) / [RTL](docs/rtl.md) | Direction, BiDi, shaping |
| [Engines](docs/engines.md) | Adapter matrix |
| [Fallback](docs/fallback.md) | Font stacks |
| [Security](docs/security.md) | SSRF, paths, headers |
| [Performance](docs/performance.md) | Cache, lazy fonts |
| [Artisan](docs/artisan.md) | CLI |
| [Testing](docs/testing.md) | Pest, fakes |
| [Troubleshooting](docs/troubleshooting.md) | Common failures |
| [Contributing](docs/contributing.md) | Dev workflow |

---

## License

The MIT License (MIT). See [LICENSE](LICENSE) for details.

Report security issues privately — see [SECURITY.md](SECURITY.md).
