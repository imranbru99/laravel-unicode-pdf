<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Universal Unicode PDF' }}</title>
    <style>
        body {
            font-family: 'Noto Sans', sans-serif;
            color: #2d3748;
            line-height: 1.6;
            margin: 20px;
        }
        h1 {
            color: #1a202c;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 8px;
        }
        .lang-card {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 12px;
        }
        .lang-name {
            font-weight: bold;
            color: #4a5568;
            font-size: 14px;
        }
        .lang-text {
            font-size: 18px;
            color: #1a202c;
            margin-top: 4px;
        }
        .rtl-block {
            text-align: right;
            direction: rtl;
        }
    </style>
</head>
<body>
    <h1>{{ $title ?? 'Universal Unicode PDF Test' }}</h1>
    <p>Testing robust multilingual, complex script, and RTL rendering capabilities.</p>

    @if(isset($languages) && is_array($languages))
        @foreach($languages as $lang => $text)
            <div class="lang-card {{ in_array($lang, ['Arabic', 'Urdu', 'Persian', 'Hebrew']) ? 'rtl-block' : '' }}">
                <div class="lang-name">{{ $lang }}</div>
                <div class="lang-text" dir="{{ in_array($lang, ['Arabic', 'Urdu', 'Persian', 'Hebrew']) ? 'rtl' : 'ltr' }}">
                    {{ $text }}
                </div>
            </div>
        @endforeach
    @else
        <div class="lang-card">
            <div class="lang-name">English</div>
            <div class="lang-text">Hello World</div>
        </div>
        <div class="lang-card">
            <div class="lang-name">বাংলা (Bengali)</div>
            <div class="lang-text">শুভ সকাল, বাংলাদেশ। রাবিতে সাড়ে ৬ বছরে ১৬ শিক্ষার্থীর আত্মহত্যা রোধে সমন্বিত উদ্যোগ।</div>
        </div>
        <div class="lang-card rtl-block">
            <div class="lang-name">العربية (Arabic)</div>
            <div class="lang-text" dir="rtl">مرحباً بالعالم — مرحباً بك في نظام الفواتير الشامل</div>
        </div>
        <div class="lang-card">
            <div class="lang-name">हिन्दी (Hindi)</div>
            <div class="lang-text">दुनिया में आपका स्वागत है — प्रौद्योगिकी और विकास</div>
        </div>
        <div class="lang-card">
            <div class="lang-name">中文 (Chinese)</div>
            <div class="lang-text">世界你好，欢迎来到统一多语言系统</div>
        </div>
    @endif
</body>
</html>
