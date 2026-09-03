<?php

declare(strict_types=1);

use ImranDev\UnicodePdf\Native\FontDownloader;

require dirname(__DIR__).'/vendor/autoload.php';

$directory = dirname(__DIR__).DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'fonts';

echo 'Downloading Noto fonts into '.$directory.PHP_EOL;

$files = FontDownloader::ensure($directory, static function (string $message): void {
    echo '  '.$message.PHP_EOL;
});

echo 'Present: '.count($files).PHP_EOL;
foreach ($files as $file) {
    echo '  '.basename($file).'  '.number_format(filesize($file)).' bytes'.PHP_EOL;
}

if ($files === []) {
    exit(1);
}
