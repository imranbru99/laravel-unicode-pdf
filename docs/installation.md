# Installation Guide

## Requirements

* **PHP**: `^8.1 | ^8.2 | ^8.3 | ^8.4 | ^8.5`
* **Laravel**: `10.x | 11.x | 12.x | 13.x`
* **PHP Extensions**:
  * `ext-mbstring` (required)
  * `ext-json` (required)
  * `ext-intl` (recommended for normalization and ICU transliteration)
  * `ext-gd` (recommended for images)

---

## Step 1: Install via Composer

```bash
composer require imrandevbd/laravel-unicode-pdf
```

---

## Step 2: Use the built-in engine (default)

No extra Composer packages are required. The **native** engine ships with the package and renders Unicode PDFs (Bengali, Arabic, Hindi, RTL, tables, TTF embedding).

```bash
# Optional — download Noto fonts for the scripts you use
php artisan unicode-pdf:font:install --font=bengali
php artisan unicode-pdf:font:install --font=arabic
php artisan unicode-pdf:font:install --font=universal
```

```env
UNICODE_PDF_ENGINE=native
```

Optional third-party engines if you already use them: `dompdf/dompdf`, `mpdf/mpdf`, `tecnickcom/tcpdf`, `spatie/browsershot`.

---

## Step 3: Publish Configuration

```bash
php artisan vendor:publish --tag=unicode-pdf-config
```

Optionally publish sample views:
```bash
php artisan vendor:publish --tag=unicode-pdf-views
```

---

## Step 4: Run System Diagnostics

```bash
php artisan unicode-pdf:diagnose
```
