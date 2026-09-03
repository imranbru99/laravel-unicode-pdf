# Right-to-Left (RTL) & Bi-directional (BiDi) Text

`laravel-unicode-pdf` provides native understanding of RTL scripts and mixed LTR/RTL content.

## Automatic Direction Detection

When `direction` is set to `'auto'`, the package inspects the dominant script in the document:

```php
use ImranDev\UnicodePdf\Facades\UnicodePdf;

// Dominant Arabic text automatically sets RTL
$pdf = UnicodePdf::direction('auto')
    ->loadHtml('<p>مرحباً بالعالم</p>');
```

## Explicit Direction

You can explicitly force direction at document level:

```php
UnicodePdf::direction('rtl');
UnicodePdf::direction('ltr');
```

Or within HTML tags:

```html
<div dir="rtl">
    <p>اسم العميل: محمد أحمد</p>
    <p>رقم الطلب: 12345</p>
</div>
```

## Mixed LTR + RTL Content

Bidirectional sentences (such as Latin product names and numbers inside Arabic text) are preserved:

```html
<div dir="rtl">
    Order #12345 — مرحباً بالعالم
</div>
```
