# Troubleshooting Guide

### 1. Bengali or Hindi characters display as empty boxes or question marks `?`
* **Cause**: The current font (e.g. Arial or Helvetica) does not contain glyphs for the Bengali or Devanagari Unicode block.
* **Solution**:
  1. Ensure `Noto Sans Bengali` or `Noto Sans Devanagari` is installed and registered.
  2. Use preset: `UnicodePdf::preset('bengali')`.
  3. Ensure `font_detection.enabled` is `true` in `config/unicode-pdf.php`.

---

### 2. Bengali conjuncts (যুক্তাক্ষর) appear broken or disconnected
* **Cause**: Basic PDF engines like Dompdf have limited OpenType ligature/shaping support.
* **Solution**: Switch to **mPDF** or **Browsershot**:
  ```php
  UnicodePdf::engine('mpdf')->preset('bengali')->loadView(...);
  ```

---

### 3. Arabic letters are disconnected or reversed
* **Cause**: Engine lacks bidirectional algorithm or cursive shaping support, or text direction is set to LTR.
* **Solution**: Use `preset('arabic')` or set `direction('rtl')` with the `mpdf` engine driver.

---

### 4. Chinese / Japanese / Korean (CJK) characters missing
* **Cause**: CJK requires large font coverage tables.
* **Solution**: Use `preset('cjk')` and configure `Noto Sans CJK SC / TC / JP / KR`.

---

### 5. PDF works in browser preview but missing text when downloaded
* **Cause**: Browser renders using operating system local fonts; PDF engines require embedded fonts inside the PDF file.
* **Solution**: Register TTF fonts via `UnicodePdf::registerFont()` so they are embedded directly into the document.
