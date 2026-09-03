<?php

declare(strict_types=1);

use ImranDev\UnicodePdf\Native\FontDownloader;
use ImranDev\UnicodePdf\Native\Shaper;
use ImranDev\UnicodePdf\Native\TtfFont;

require dirname(__DIR__).'/vendor/autoload.php';

$dir = dirname(__DIR__).DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'fonts';
FontDownloader::ensure($dir, static function (string $m): void {
    echo $m.PHP_EOL;
});

$path = $dir.DIRECTORY_SEPARATOR.'NotoSansBengali-Regular.ttf';
echo 'bengali bytes='.filesize($path).PHP_EOL;

$font = TtfFont::load($path);
$pres = 0;
$half = 0;
foreach ($font->lookups() as $lookup) {
    $features = $lookup['features'] ?? [];
    if (in_array('pres', $features, true) && ($lookup['type'] ?? 0) === 4) {
        foreach ($lookup['ligatures'] ?? [] as $first => $cands) {
            $pres += count($cands);
        }
    }
    if (in_array('half', $features, true) && ($lookup['type'] ?? 0) === 1) {
        $half += count($lookup['substitutes'] ?? []);
    }
}
echo "pres ligatures={$pres} half substitutes={$half} lookups=".count($font->lookups()).PHP_EOL;

foreach (['মোট', 'ঠিকানা', 'কম্পিউটার', 'পণ্যের', 'ল্যাপটপ', 'ধন্যবাদ', 'বাংলাদেশ', 'আমাদের', 'সর্বমোট', 'ধানমন্ডি'] as $word) {
    $shaped = Shaper::shape($word, $font, false);
    echo $word.' '.count(Shaper::codepoints($word)).'→'.count($shaped['gids']).' gids='.implode(',', $shaped['gids']).PHP_EOL;
}
