# Performance & Optimization

## 1. Font Metadata & Parsing Cache
Large TTF/OTF fonts can take time to parse. The package caches parsed font tables and Unicode metrics inside `storage/app/unicode-pdf/cache/`.

Clear cache using Artisan:
```bash
php artisan unicode-pdf:clear-cache
```

## 2. Lazy Font Loading
Only fonts detected in the document content or specified in the fallback chain are injected into the CSS/font subsetting pipeline.

## 3. Queue Workers
`laravel-unicode-pdf` maintains no shared global mutable state, making it safe for long-running Laravel queue workers:

```php
dispatch(function () use ($invoice) {
    UnicodePdf::loadView('pdf.invoice', ['invoice' => $invoice])
        ->store("invoices/{$invoice->id}.pdf", 's3');
});
```
