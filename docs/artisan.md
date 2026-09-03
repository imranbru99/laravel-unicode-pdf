# Artisan Commands

| Command | Purpose |
| :--- | :--- |
| `php artisan unicode-pdf:diagnose` | Run comprehensive system, extension, engine, and font diagnostics |
| `php artisan unicode-pdf:fonts` | Display summary of default, registered, and fallback fonts with coverage |
| `php artisan unicode-pdf:font:list` | Table listing registered font families, formats, glyph counts, and paths |
| `php artisan unicode-pdf:font:install` | Interactive download and setup guide for open-source Google Noto fonts |
| `php artisan unicode-pdf:clear-cache` | Clear font cache and temporary PDF generation files |
| `php artisan unicode-pdf:generate` | Generate a PDF from a Blade view or `--html` string |

## Generate

```bash
php artisan unicode-pdf:generate invoices.show --output=storage/app/invoice.pdf --preset=bengali --paper=a4
php artisan unicode-pdf:generate --html="<h1>বাংলাদেশ</h1>" --output=/tmp/out.pdf --engine=null
```

