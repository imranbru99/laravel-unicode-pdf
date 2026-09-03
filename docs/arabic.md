# Arabic, Persian & Urdu Support

## Arabic Typography & Shaping

Arabic characters connect differently depending on their position (isolated, initial, medial, final).

### Recommended Engine

For high quality Arabic cursive shaping and RTL justification, **mPDF** or **Browsershot** is strongly recommended:

```php
use ImranDev\UnicodePdf\Facades\UnicodePdf;

return UnicodePdf::engine('mpdf')
    ->preset('arabic')
    ->loadView('pdf.invoice-arabic', [
        'customer_name' => 'محمد أحمد',
        'invoice_no' => '١٢٣٤٥',
        'total' => '٦٬٠٠٠ ر.س',
    ])
    ->download('invoice-arabic.pdf');
```

### Font Recommendations
* **Noto Sans Arabic** (modern UI)
* **Amiri** (traditional Naskh calligraphy)
* **Scheherazade New** (classic Arabic typesetting)
* **Noto Nastaliq Urdu** (Urdu Nastaliq script)
