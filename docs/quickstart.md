# Quickstart Guide

## 1. Zero-Configuration Example

```php
use ImranDev\UnicodePdf\Facades\UnicodePdf;

// Download a PDF directly from Blade view
return UnicodePdf::loadView('invoices.show', [
    'customer' => 'মোহাম্মদ ইমরান',
    'total' => '৳৮৫,০০০',
])
->setPaper('a4')
->download('invoice.pdf');
```

---

## 2. Any language + HTML / CSS

Use Blade or HTML. Style with `<style>`, `class`, inline `style`, or `->css()` / `->fontSize()`.

```php
$html = <<<'HTML'
<style>
    h1 { font-size: 22pt; color: #1a365d; text-align: center; }
    .amount { font-size: 16pt; color: #c53030; font-weight: bold; }
    p { font-size: 12pt; line-height: 1.5; }
</style>
<h1>Universal Unicode Document</h1>
<p>English: Hello World</p>
<p>বাংলা: শুভ সকাল, বাংলাদেশ</p>
<p class="amount">৳৮০,০০০</p>
<p>Arabic: مرحباً بالعالم</p>
<p>Hindi: दुनिया में आपका स्वागत है</p>
<p>中文 / 日本語 / 한국어</p>
HTML;

return UnicodePdf::preset('universal')
    ->loadHtml($html)
    ->fontSize(12)
    ->css('.amount { text-decoration: underline; }')
    ->download('multilingual.pdf');
```

Per-language shortcuts:

```php
UnicodePdf::locale('bn')->loadView('invoices.show', $data)->download(); // Bengali
UnicodePdf::locale('ar')->loadView('invoices.show', $data)->download(); // Arabic RTL
UnicodePdf::locale('hi')->loadView('invoices.show', $data)->download(); // Hindi
UnicodePdf::locale('ja')->loadView('invoices.show', $data)->download(); // Japanese
```

Supported CSS in the native engine: `font-size` (pt/px/em/rem/%/mm), `font-family`, `font-weight`, `font-style`, `color`, `background-color`, `text-align`, `text-decoration`, `line-height`, `margin`, `padding`, `direction`.

---

## 3. Streaming (Preview in Browser)

```php
return UnicodePdf::loadView('pdf.report', $data)
    ->stream('report.pdf');
```

---

## 4. Saving to Local Storage or S3

```php
// Local filesystem
UnicodePdf::loadView('pdf.receipt', $data)
    ->save(storage_path('app/receipts/receipt-101.pdf'));

// Laravel Storage disk (e.g. s3, public, local)
UnicodePdf::loadView('pdf.receipt', $data)
    ->store('receipts/receipt-101.pdf', 's3');
```

---

## 5. Modern Laravel DX

```php
// Enums, conditionable API, and native HTTP response
return UnicodePdf::engine(\ImranDev\UnicodePdf\Enums\Engine::Null)
    ->preset(\ImranDev\UnicodePdf\Enums\Preset::Bengali)
    ->paper(\ImranDev\UnicodePdf\Enums\PaperSize::A4)
    ->when($watermark, fn ($pdf) => $pdf->watermark('DRAFT'))
    ->name('চালান.pdf');

// Named profile from config/unicode-pdf.php
return UnicodePdf::profile('invoice')->loadView('invoices.show', $data);

// Queue / cache
UnicodePdf::loadView('invoices.show', $data)->queue('invoices/101.pdf', 's3');
UnicodePdf::loadView('invoices.show', $data)->cache(3600)->download('invoice.pdf');
```

