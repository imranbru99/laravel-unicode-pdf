# Font Fallback Mechanism

## How Font Fallback Works

When rendering mixed-script documents (e.g. English + Bengali + Arabic in the same paragraph), no single standard font contains glyphs for every global language.

`laravel-unicode-pdf` builds an intelligent fallback stack:

```php
use ImranDev\UnicodePdf\Facades\UnicodePdf;

UnicodePdf::font('Noto Sans')
    ->fallback([
        'Noto Sans Bengali',
        'Noto Sans Arabic',
        'Noto Sans Devanagari',
        'Noto Sans CJK SC',
    ])
    ->loadHtml('<p>Hello বাংলা مرحباً दुनिया 世界</p>');
```

### Automatic Fallback Generation
When `font_detection.enabled` is active, the package automatically detects scripts and appends appropriate fonts to the `@font-face` and CSS `font-family` declaration stack without requiring manual per-script configuration.
