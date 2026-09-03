# PDF Engines & Capability Matrix

`laravel-unicode-pdf` ships a **native** PDF engine. You do not need Dompdf, mPDF, or any other Composer PDF library.

## Built-in engine (default)

| Engine | Package | RTL | Complex shaping | CSS | Best for |
| :--- | :--- | :---: | :---: | :---: | :--- |
| **Native** | *(bundled)* | Yes | Arabic joining + Indic GSUB ligatures | HTML, tables, basic CSS | Default. Zero extra dependencies |

Install TTF fonts once:

```bash
php artisan unicode-pdf:font:install --font=bengali
php artisan unicode-pdf:font:install --font=arabic
```

```php
return UnicodePdf::engine('native')
    ->preset('bengali')
    ->loadView('invoices.show', $data)
    ->download('invoice.pdf');
```

## Optional third-party adapters

These stay available if you already have them installed. They are **not required**.

| Engine | Package | RTL Support | Complex Shaping | CSS Modernity | Best For |
| :--- | :--- | :---: | :---: | :---: | :--- |
| **Dompdf** | `dompdf/dompdf` | Limited | Limited | CSS 2.1 | Existing Dompdf apps |
| **mPDF** | `mpdf/mpdf` | Full | Full (Arabic & Indic) | Moderate | Comparison / migration |
| **TCPDF** | `tecnickcom/tcpdf` | Moderate | Limited | Basic | Legacy TCPDF apps |
| **Browsershot** | `spatie/browsershot` | Full | Full (HarfBuzz) | CSS Grid/Flexbox | Pixel-perfect Chromium output |

---

## Checking Capabilities Programmatically

```php
use ImranDev\UnicodePdf\Facades\UnicodePdf;

if (UnicodePdf::engine('mpdf')->supports('font-shaping')) {
    // Engine supports Bengali and Indic conjunct shaping
}

if (UnicodePdf::supports('rtl')) {
    // Default engine supports RTL
}
```
