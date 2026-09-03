<?php

declare(strict_types=1);

use ImranDev\UnicodePdf\Facades\UnicodePdf;
use ImranDev\UnicodePdf\Native\FontDownloader;
use ImranDev\UnicodePdf\UnicodePdfServiceProvider;
use Orchestra\Testbench\Foundation\Application as Testbench;

require dirname(__DIR__).'/vendor/autoload.php';

$packageRoot = dirname(__DIR__);
$fontDir = $packageRoot.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'fonts';
$outDir = $packageRoot.DIRECTORY_SEPARATOR.'samples';

$app = Testbench::create(
    basePath: $packageRoot.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'orchestra'.DIRECTORY_SEPARATOR.'testbench-core'.DIRECTORY_SEPARATOR.'laravel',
    options: ['extra' => ['dont-discover' => ['*']]]
);
$app->register(UnicodePdfServiceProvider::class);

$app['config']->set('unicode-pdf.engine', 'native');
$app['config']->set('unicode-pdf.default_font', 'Noto Sans');
$app['config']->set('unicode-pdf.font_path', $fontDir);
$app['config']->set('unicode-pdf.font_cache', sys_get_temp_dir().'/unicode_pdf_sample_cache');
$app['config']->set('unicode-pdf.security.allowed_local_paths', [$packageRoot, sys_get_temp_dir()]);

echo 'Ensuring Noto Unicode fonts...'.PHP_EOL;
FontDownloader::ensure($fontDir, static function (string $message): void {
    echo '  '.$message.PHP_EOL;
});

if (! is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

$manager = $app->make('unicode-pdf');
$fonts = $manager->getFontManager();

$candidates = [
    'Noto Sans' => [
        $fontDir.DIRECTORY_SEPARATOR.'NotoSans-Regular.ttf',
        'C:/Windows/Fonts/arial.ttf',
        'C:/Windows/Fonts/segoeui.ttf',
        'C:/Windows/Fonts/calibri.ttf',
    ],
    'Noto Sans Bengali' => [
        $fontDir.DIRECTORY_SEPARATOR.'NotoSansBengali-Regular.ttf',
        'C:/Windows/Fonts/Nirmala.ttf',
        'C:/Windows/Fonts/vrinda.ttf',
    ],
    'Noto Sans Arabic' => [
        $fontDir.DIRECTORY_SEPARATOR.'NotoSansArabic-Regular.ttf',
        'C:/Windows/Fonts/tahoma.ttf',
        'C:/Windows/Fonts/arial.ttf',
    ],
    'Noto Sans Devanagari' => [
        $fontDir.DIRECTORY_SEPARATOR.'NotoSansDevanagari-Regular.ttf',
        'C:/Windows/Fonts/Nirmala.ttf',
        'C:/Windows/Fonts/mangal.ttf',
    ],
    'Noto Sans Hebrew' => [
        $fontDir.DIRECTORY_SEPARATOR.'NotoSansHebrew-Regular.ttf',
        'C:/Windows/Fonts/arial.ttf',
        'C:/Windows/Fonts/tahoma.ttf',
    ],
    'Noto Sans Thai' => [
        $fontDir.DIRECTORY_SEPARATOR.'NotoSansThai-Regular.ttf',
        'C:/Windows/Fonts/LeelawUI.ttf',
        'C:/Windows/Fonts/Nirmala.ttf',
    ],
    'Noto Sans Tamil' => ['C:/Windows/Fonts/Nirmala.ttc', 'C:/Windows/Fonts/Nirmala.ttf'],
    'Noto Sans Telugu' => ['C:/Windows/Fonts/Nirmala.ttc', 'C:/Windows/Fonts/Nirmala.ttf'],
    'Noto Sans Malayalam' => ['C:/Windows/Fonts/Nirmala.ttc', 'C:/Windows/Fonts/Nirmala.ttf'],
    'Noto Sans Gujarati' => ['C:/Windows/Fonts/Nirmala.ttc', 'C:/Windows/Fonts/Nirmala.ttf'],
    'Noto Sans Gurmukhi' => ['C:/Windows/Fonts/Nirmala.ttc', 'C:/Windows/Fonts/Nirmala.ttf'],
    'Noto Sans Kannada' => ['C:/Windows/Fonts/Nirmala.ttc', 'C:/Windows/Fonts/Nirmala.ttf'],
    'Noto Sans CJK SC' => ['C:/Windows/Fonts/msyh.ttc', 'C:/Windows/Fonts/simsun.ttc'],
    'Noto Sans CJK JP' => ['C:/Windows/Fonts/YuGothR.ttc', 'C:/Windows/Fonts/msgothic.ttc'],
    'Noto Sans CJK KR' => ['C:/Windows/Fonts/malgun.ttf'],
];

$aiRegular = null;
foreach ([
    'C:/Users/imran/Documents/server/font/dist/AI-Borno-Regular.ttf',
    $fontDir.DIRECTORY_SEPARATOR.'AI-Borno-Regular.ttf',
] as $path) {
    if (is_readable($path)) {
        $aiRegular = $path;
        break;
    }
}
$aiBold = null;
foreach ([
    'C:/Users/imran/Documents/server/font/dist/AI-Borno-Bold.ttf',
    $fontDir.DIRECTORY_SEPARATOR.'AI-Borno-Bold.ttf',
] as $path) {
    if (is_readable($path)) {
        $aiBold = $path;
        break;
    }
}
if ($aiRegular) {
    $fonts->register(array_filter([
        'family' => 'AI-Borno',
        'regular' => $aiRegular,
        'bold' => $aiBold,
    ]));
    echo "font  AI-Borno  <-  {$aiRegular}".PHP_EOL;
    if ($aiBold) {
        echo "font  AI-Borno Bold  <-  {$aiBold}".PHP_EOL;
    }
}

foreach ($candidates as $family => $paths) {
    foreach ($paths as $path) {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (is_readable($path) && in_array($ext, ['ttf', 'ttc'], true)) {
            $fonts->register(['family' => $family, 'regular' => $path]);
            echo "font  {$family}  <-  {$path}".PHP_EOL;
            break;
        }
    }
}

if (is_dir($fontDir)) {
    $fonts->discoverDirectory($fontDir);
}

$jobs = [
    '01-english-styled.pdf' => static fn () => UnicodePdf::engine('native')
        ->loadHtml(<<<'HTML'
            <style>
                h1 { font-size: 22pt; color: #1a365d; text-align: center; }
                p { font-size: 12pt; line-height: 1.5; }
                .total { font-size: 16pt; color: #c53030; font-weight: bold; }
            </style>
            <h1>Laravel Unicode PDF</h1>
            <p>Native engine sample — no Dompdf or mPDF.</p>
            <p class="total">Total: $90,000</p>
        HTML)
        ->fontSize(12),
    '02-bengali-invoice.pdf' => static fn () => UnicodePdf::engine('native')
        ->preset('bengali')
        ->locale('bn')
        ->loadView('unicode-pdf::invoice-bengali', [
            'invoice_no' => 'ইনভ-২০২৬-১০০১',
            'date' => '০১ সেপ্টেম্বর ২০২৬',
            'customer_name' => 'মোহাম্মদ ইমরান হোসেন',
            'customer_address' => 'ধানমন্ডি, ঢাকা - ১২০৯, বাংলাদেশ',
        ]),
    '03-arabic-invoice.pdf' => static fn () => UnicodePdf::engine('native')
        ->preset('arabic')
        ->locale('ar')
        ->loadView('unicode-pdf::invoice-arabic'),
    '04-hindi-invoice.pdf' => static fn () => UnicodePdf::engine('native')
        ->preset('indian')
        ->locale('hi')
        ->loadView('unicode-pdf::invoice-hindi'),
    '05-multilingual.pdf' => static fn () => UnicodePdf::engine('native')
        ->preset('universal')
        ->loadView('unicode-pdf::sample-multilingual', [
            'title' => 'Universal Unicode PDF Sample',
            'languages' => [
                'English' => 'Hello World',
                'Bengali' => 'শুভ সকাল বাংলাদেশ',
                'Arabic' => 'مرحباً بالعالم',
                'Hindi' => 'दुनिया में आपका स्वागत है',
                'Urdu' => 'دنیا میں خوش آمدید',
                'Chinese' => '世界你好',
                'Japanese' => 'こんにちは世界',
                'Korean' => '안녕하세요 세계',
                'Russian' => 'Привет мир',
                'Hebrew' => 'שלום עולם',
                'Thai' => 'สวัสดีชาวโลก',
            ],
        ]),
    '06-mixed-html-css.pdf' => static fn () => UnicodePdf::engine('native')
        ->preset('universal')
        ->fontSize(12)
        ->css('h1 { text-align: center; color: #2b6cb0; } .box { background-color: #ebf8ff; padding: 8pt; }')
        ->loadHtml(<<<'HTML'
            <h1 style="font-size: 20pt;">Mixed language + CSS</h1>
            <div class="box">
                <p style="font-size: 14pt;">বাংলা: শিক্ষার্থী ৳৮০,০০০</p>
                <p style="font-size: 14pt; direction: rtl; text-align: right;">العربية: مرحباً بالعالم</p>
                <p style="font-size: 14pt;">हिन्दी: नमस्ते दुनिया</p>
                <p style="font-size: 11pt; color: #718096;">English note — styled with font-size and color.</p>
            </div>
        HTML),
    '07-all-language-story.pdf' => static function () use ($packageRoot) {
        $stories = require $packageRoot.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'stories'.DIRECTORY_SEPARATOR.'all-languages.php';

        return UnicodePdf::engine('native')
            ->preset('universal')
            ->fontSize(11)
            ->loadView('unicode-pdf::story-all-languages', [
                'title' => 'The Book by the River',
                'stories' => $stories,
            ]);
    },
];

foreach ($jobs as $name => $factory) {
    $path = $outDir.DIRECTORY_SEPARATOR.$name;
    file_put_contents($path, $factory()->output());
    $bytes = is_file($path) ? filesize($path) : 0;
    echo $name.'  '.number_format((int) $bytes).' bytes  ->  '.$path.PHP_EOL;
}

echo PHP_EOL.'Samples written to '.$outDir.PHP_EOL;
