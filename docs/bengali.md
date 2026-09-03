# Bengali Language Support

Bengali is a first-class script in `laravel-unicode-pdf`.

## Tested Complex Conjuncts & Car

* কারসমূহ: `ি`, `ী`, `ে`, `ৈ`, `ো`, `ৌ`, `ং`, `ঃ`, `ঁ`
* যুক্তাক্ষর: `কৃষ্ণ`, `শিক্ষার্থী`, `স্বাধীনতা`, `প্রযুক্তি`, `বাংলাদেশ`
* সংখ্যা ও মুদ্রা: `১, ২, ৩, ৪, ৫, ৬, ৭, ৮, ৯, ০` ও `৳` (Taka symbol)

## Recommended Setup

For optimal glyph shaping (correctly rendering complex conjuncts like `ক্ষ`, `ষ্ণ`, `র্থী` without disconnected characters), use **mPDF** or **Browsershot**:

```php
use ImranDev\UnicodePdf\Facades\UnicodePdf;

return UnicodePdf::engine('mpdf')
    ->preset('bengali')
    ->loadView('pdf.invoice-bengali', $data)
    ->download('invoice.pdf');
```
