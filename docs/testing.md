# Testing Guide

## Running Tests

Execute the full test suite using Pest:

```bash
composer test
```

Run specific test suites:

```bash
composer run test:unit
composer run test:feature
```

## Running Code Linter & Static Analysis

```bash
# Code formatting test
composer run lint

# Auto fix styling
composer run fix

# PHPStan static analysis
composer run analyse
```

## Faking PDF generation

```php
use ImranDev\UnicodePdf\Facades\UnicodePdf;

UnicodePdf::fake();

$this->get('/invoices/101/pdf')->assertOk();

UnicodePdf::assertGenerated();
UnicodePdf::assertDownloaded('invoice.pdf');
UnicodePdf::assertNothingGenerated(); // when no PDF should be built
```

