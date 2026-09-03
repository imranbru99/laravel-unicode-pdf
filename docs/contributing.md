# Contributing Guidelines

Thank you for considering contributing to `laravel-unicode-pdf`!

## Pull Request Process

1. Fork the repository and create your branch from `main`.
2. Ensure you adhere to PSR-12 and Laravel Pint code style (`composer run fix`).
3. Add unit or feature tests for any new features or bug fixes.
4. Ensure all tests pass (`composer test`).
5. Open a Pull Request with a clear description of the problem and solution.

## Adding Support for New Scripts or Fonts

* Add script range regex in `src/Unicode/ScriptDetector.php`.
* Add font mappings in `src/Fonts/FontDetector.php` and `src/Unicode/LocaleMapper.php`.
* Include test cases covering vowels, conjuncts, diacritics, and numerals in `tests/Feature/MultilingualRenderingTest.php`.
