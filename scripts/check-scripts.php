<?php

declare(strict_types=1);

use ImranDev\UnicodePdf\Native\FontLibrary;
use ImranDev\UnicodePdf\Native\Shaper;
use ImranDev\UnicodePdf\Native\TtfFont;

require dirname(__DIR__).'/vendor/autoload.php';

$fonts = FontLibrary::packageFontPath();

$suites = [
    'Bengali' => [
        'font' => $fonts.DIRECTORY_SEPARATOR.'AI-Borno-Regular.ttf',
        'virama' => 0x09CD,
        'words' => [
            'বাংলা', 'বাংলাদেশ', 'মোট', 'ঠিকানা', 'বিক্রয়', 'ক্রেতার', 'ধন্যবাদ',
            'পণ্যের', 'ল্যাপটপ', 'কম্পিউটার', 'ব্যবসা', 'জন্য', 'মোহাম্মদ',
            'ইমরান', 'হোসেন', 'ধানমন্ডি', 'সেপ্টেম্বর', 'চালানপত্র', 'ক্রমিক',
            'পরিমাণ', 'মূল্য', 'সর্বমোট', 'শিক্ষার্থী', 'স্বাধীনতা', 'প্রোগ্রামিং',
            'ক্ষুদ্রঋণ', 'শ্রদ্ধাঞ্জলি', 'বিজ্ঞান', 'জ্ঞ', 'ক্ষ', 'ত্র', 'ক্র',
            'ন্য', 'ল্যা', 'ষ্ট্র', 'অঙ্ক', 'সত্য', 'স্কুল', 'হৃদয়',
            'আমার সোনার বাংলা', 'শুভ সকাল', 'পরের', 'পৃষ্ঠা', 'কথা', 'জিজ্ঞেস',
            'আমগাছের', 'সরে', 'দিয়ে', 'ভাষায়',
        ],
    ],
    'Hindi' => [
        'font' => $fonts.DIRECTORY_SEPARATOR.'NotoSansDevanagari-Regular.ttf',
        'virama' => 0x094D,
        'words' => [
            'हिन्दी', 'दुनिया', 'स्वागत', 'बिक्री', 'चालान', 'ग्राहक', 'शर्मा',
            'नई दिल्ली', 'भारत', 'उत्पाद', 'विवरण', 'मात्रा', 'मूल्य',
            'स्मार्टफोन', 'प्रौद्योगिकी', 'विकास', 'क्र.सं.', 'क्ष', 'त्र',
            'श्र', 'ज्ञ', 'प्र', 'स्त', 'द्वि', 'क्रिया', 'विद्यालय',
            'नमस्ते', 'आपका', 'सितम्बर', 'की', 'मिली', 'लगभग',
        ],
    ],
    'Arabic' => [
        'font' => $fonts.DIRECTORY_SEPARATOR.'NotoSansArabic-Regular.ttf',
        'virama' => 0,
        'words' => [
            'مرحباً', 'بالعالم', 'فاتورة', 'الاسم', 'العنوان', 'الإجمالي',
            'شكراً', 'العربية',
        ],
    ],
    'Thai' => [
        'font' => $fonts.DIRECTORY_SEPARATOR.'NotoSansThai-Regular.ttf',
        'virama' => 0,
        'words' => ['สวัสดี', 'ชาวโลก', 'ประเทศไทย'],
    ],
    'Hebrew' => [
        'font' => $fonts.DIRECTORY_SEPARATOR.'NotoSansHebrew-Regular.ttf',
        'virama' => 0,
        'words' => ['שלום', 'עולם'],
    ],
];

foreach ($suites as $script => $suite) {
    if (! is_readable($suite['font'])) {
        echo "SKIP {$script} (no font)".PHP_EOL.PHP_EOL;

        continue;
    }

    $font = TtfFont::load($suite['font']);
    $virama = $suite['virama'] ? $font->glyphId($suite['virama']) : 0;
    $fail = [];
    $ok = 0;
    echo "=== {$script}  {$font->family} ===".PHP_EOL;
    foreach ($suite['words'] as $word) {
        $cps = Shaper::codepoints($word);
        $shaped = Shaper::shape($word, $font, Shaper::isRtlText($word));
        $gids = $shaped['gids'];
        $issues = [];
        if ($virama > 0 && in_array($virama, $gids, true) && preg_match('/\x{094D}|\x{09CD}/u', $word)) {
            $issues[] = 'raw-virama';
        }
        $dropped = count($cps) - count($gids);
        if (count($gids) === 0 && $word !== '') {
            $issues[] = 'empty';
        }
        if ($issues !== []) {
            $fail[] = $word.' ['.implode(',', $gids).'] '.implode(',', $issues);
            echo '  FAIL  '.$word.'  '.count($cps).'→'.count($gids).'  '.implode(',', $issues).PHP_EOL;
        } else {
            $ok++;
            echo '  ok    '.$word.'  '.count($cps).'→'.count($gids).PHP_EOL;
        }
    }
    echo "  {$ok}/".count($suite['words']).' passed'.($fail !== [] ? ', '.count($fail).' flagged' : '').PHP_EOL.PHP_EOL;
}
