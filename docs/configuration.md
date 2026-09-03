# Configuration Reference

The published configuration file is located at `config/unicode-pdf.php`.

```php
return [

    // Default engine: 'native' (built-in). Optional: 'dompdf', 'mpdf', 'tcpdf', 'browsershot', 'null'
    'engine' => env('UNICODE_PDF_ENGINE', 'native'),

    // Primary font family
    'default_font' => 'Noto Sans',

    // Fallback fonts stack
    'fallback_fonts' => [
        'Noto Sans',
        'Noto Sans Bengali',
        'Noto Sans Arabic',
        'Noto Sans Devanagari',
        'Noto Sans Thai',
        'Noto Sans Hebrew',
        'Noto Sans CJK SC',
    ],

    // Custom registered fonts
    'fonts' => [
        'Kalpurush' => [
            'regular' => storage_path('app/unicode-pdf/fonts/kalpurush.ttf'),
            'bold'    => storage_path('app/unicode-pdf/fonts/kalpurush-bold.ttf'),
        ],
    ],

    // Storage and cache
    'font_path' => storage_path('app/unicode-pdf/fonts'),
    'font_cache' => storage_path('app/unicode-pdf/cache/fonts'),

    // Script & Font Auto Detection
    'font_detection' => [
        'enabled' => true,
        'auto_inject_css' => true,
    ],

    // Text direction: 'auto', 'ltr', 'rtl'
    'direction' => 'auto',
    'bidi' => true,

    // Normalization: 'NFC', 'NFD', 'NFKC', 'NFKD'
    'normalization' => [
        'enabled' => false,
        'form' => 'NFC',
    ],

    // Security settings
    'security' => [
        'allow_remote_images' => false,
        'allow_remote_fonts' => false,
        'allowed_remote_hosts' => [],
        'allowed_local_paths' => [
            base_path(),
            storage_path(),
            public_path(),
        ],
    ],

    // Named document profiles: UnicodePdf::profile('invoice')
    'profiles' => [
        'invoice' => [
            'preset' => 'universal',
            'paper' => 'a4',
        ],
    ],
];
```

