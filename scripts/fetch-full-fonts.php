<?php

declare(strict_types=1);

use ImranDev\UnicodePdf\Native\FontDownloader;

require dirname(__DIR__).'/vendor/autoload.php';

$urls = [
    'jsdelivr-ghio' => 'https://cdn.jsdelivr.net/gh/notofonts/notofonts.github.io/fonts/NotoSansBengali/unhinted/ttf/NotoSansBengali-Regular.ttf',
    'fontsource' => 'https://cdn.jsdelivr.net/fontsource/fonts/noto-sans-bengali@5.2.5/bengali-400-normal.ttf',
    'fontsource-files' => 'https://cdn.jsdelivr.net/npm/@fontsource/noto-sans-bengali@5.2.5/files/noto-sans-bengali-bengali-400-normal.ttf',
    'googlefonts-hinted' => 'https://github.com/googlefonts/noto-fonts/raw/main/hinted/ttf/NotoSansBengali/NotoSansBengali-Regular.ttf',
    'googlefonts-unhinted' => 'https://github.com/googlefonts/noto-fonts/raw/main/unhinted/ttf/NotoSansBengali/NotoSansBengali-Regular.ttf',
    'gf-variable' => 'https://github.com/google/fonts/raw/main/ofl/notosansbengali/NotoSansBengali%5Bwdth%2Cwght%5D.ttf',
    'jsdelivr-release' => 'https://github.com/notofonts/bengali/releases/download/NotoSansBengali-v2.003/NotoSansBengali-Regular.ttf',
];

foreach ($urls as $name => $url) {
    $data = FontDownloader::download([$url]);
    $len = is_string($data) ? strlen($data) : 0;
    $magic = $len > 4 ? bin2hex(substr((string) $data, 0, 4)) : '-';
    echo sprintf("%-22s %8d  %s  %s\n", $name, $len, $magic, $url);
}
