# Unicode Subsystem & Character Support

## Supported Scripts

The package includes automated script detection and font resolution for:

* **Bengali / Bangla**: `[\u0980-\u09FF]` (কার, যুক্তাক্ষর, ি, ী, ে, ৈ, ো, ৌ, ং, ঃ, ঁ, Bengali numerals, ৳ currency)
* **Arabic / Urdu / Persian**: `[\u0600-\u06FF]`, presentation forms, diacritics, Eastern Arabic numerals
* **Devanagari**: Hindi, Marathi, Nepali (`[\u0900-\u097F]`)
* **Tamil, Telugu, Malayalam, Gujarati, Gurmukhi, Kannada, Sinhala**
* **Thai**: `[\u0E00-\u0E7F]`
* **Hebrew**: `[\u0590-\u05FF]`
* **CJK**: Simplified Chinese, Traditional Chinese, Japanese Kanji, Korean Hangul
* **Cyrillic & Greek**: Russian, Ukrainian, Bulgarian, Greek

---

## UTF-8 Validation

```php
use ImranDev\UnicodePdf\Facades\UnicodePdf;

UnicodePdf::validateUtf8($text); // Throws InvalidUtf8Exception with exact byte offset if invalid
```

---

## Unicode Normalization

```php
// Normalize to NFC form
$normalized = UnicodePdf::normalize($rawText, 'NFC');
```
