# Font Management & Registration

## Registering Fonts Dynamically

You can register custom fonts at runtime via `UnicodePdf::registerFont()`:

```php
use ImranDev\UnicodePdf\Facades\UnicodePdf;

UnicodePdf::registerFont([
    'family' => 'MyCustomBengaliFont',
    'regular' => storage_path('app/unicode-pdf/fonts/Custom-Regular.ttf'),
    'bold' => storage_path('app/unicode-pdf/fonts/Custom-Bold.ttf'),
    'italic' => storage_path('app/unicode-pdf/fonts/Custom-Italic.ttf'),
    'bold_italic' => storage_path('app/unicode-pdf/fonts/Custom-BoldItalic.ttf'),
]);
```

---

## Supported Font Formats

* **TTF** (TrueType Font) - Widely supported across all engines.
* **OTF** (OpenType Font) - Supported on modern engines (mPDF, Browsershot).
* **WOFF / WOFF2** - Supported primarily with Browsershot / Chromium.

---

## Presets

Built-in typography presets automatically configure fonts, fallbacks, and text direction:

```php
UnicodePdf::preset('bengali');   // Noto Sans Bengali, LTR, complex conjunct shaping
UnicodePdf::preset('arabic');    // Noto Sans Arabic / Amiri, RTL, BiDi
UnicodePdf::preset('indian');    // Noto Sans Devanagari, Hindi / Marathi
UnicodePdf::preset('cjk');       // Noto Sans CJK SC/TC/JP/KR
UnicodePdf::preset('universal'); // Comprehensive multi-script fallback stack
UnicodePdf::preset('thai');
UnicodePdf::preset('hebrew');
UnicodePdf::preset('persian');
UnicodePdf::preset('urdu');
UnicodePdf::preset('tamil');
UnicodePdf::preset('japanese');
UnicodePdf::preset('korean');
UnicodePdf::preset('khmer');
UnicodePdf::preset('myanmar');
UnicodePdf::preset('ethiopic');
UnicodePdf::preset('sinhala');
```

Locale aliases (`bn`, `ar`, `fa`, `ur`, `ja`, `ko`, `th`, `he`, …) resolve to the same presets.

