<?php

declare(strict_types=1);

use ImranDev\UnicodePdf\Native\FontDownloader;
use ImranDev\UnicodePdf\Native\TtfFont;

require dirname(__DIR__).'/vendor/autoload.php';

$files = [
    dirname(__DIR__).'/resources/fonts/NotoSansBengali-Regular.ttf',
];

$hinted = FontDownloader::download([
    'https://github.com/googlefonts/noto-fonts/raw/main/hinted/ttf/NotoSansBengali/NotoSansBengali-Regular.ttf',
]);
if (is_string($hinted)) {
    $tmp = sys_get_temp_dir().'/NotoSansBengali-Hinted.ttf';
    file_put_contents($tmp, $hinted);
    $files[] = $tmp;
    echo 'hinted bytes='.strlen($hinted).PHP_EOL;
}

foreach ($files as $file) {
    $font = TtfFont::load($file);
    $tags = [];
    $typed = [];
    $withFeatures = 0;
    foreach ($font->lookups() as $lookup) {
        $typed[$lookup['type'] ?? 0] = ($typed[$lookup['type'] ?? 0] ?? 0) + 1;
        $features = $lookup['features'] ?? [];
        if ($features !== []) {
            $withFeatures++;
        }
        foreach ($features as $tag) {
            $tags[$tag] = ($tags[$tag] ?? 0) + 1;
        }
    }
    echo basename($file).' lookups='.count($font->lookups()).' tagged='.$withFeatures.' types='.json_encode($typed).PHP_EOL;
    echo '  tags='.json_encode($tags).PHP_EOL;
}
