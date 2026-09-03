<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'The Book by the River — all languages' }}</title>
    <style>
        body {
            font-family: 'Noto Sans', 'AI-Borno', sans-serif;
            color: #1a202c;
            margin: 28px;
            line-height: 1.55;
        }
        .cover h1 {
            font-size: 22pt;
            color: #1a365d;
            margin: 0 0 8px 0;
        }
        .cover p {
            font-size: 11pt;
            color: #4a5568;
        }
        .story {
            page-break-before: always;
        }
        .lang-label {
            font-size: 9pt;
            color: #718096;
            margin: 0 0 6px 0;
        }
        .story h1 {
            font-size: 16pt;
            color: #2b6cb0;
            margin: 0 0 10px 0;
        }
        .story p {
            font-size: 11.5pt;
            margin: 0 0 9px 0;
        }
        .rtl {
            direction: rtl;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="cover">
        <h1>{{ $title ?? 'The Book by the River' }}</h1>
        <p>Same short story, one full page per language. Check letters, matras, conjuncts, and RTL joining.</p>
        <p>{{ count($stories ?? []) }} languages — English, Bangla, Hindi, Arabic, Urdu, Persian, Hebrew, Thai, Russian, Ukrainian, Greek, Latin, Indic, and CJK.</p>
    </div>

    @foreach(($stories ?? []) as $story)
        <section class="story {{ ($story['dir'] ?? 'ltr') === 'rtl' ? 'rtl' : '' }}" lang="{{ $story['id'] ?? 'en' }}" dir="{{ $story['dir'] ?? 'ltr' }}" style="font-family: '{{ $story['font'] ?? 'Noto Sans' }}', 'Noto Sans', sans-serif;">
            <p class="lang-label">{{ $story['name'] ?? '' }} · {{ $story['native'] ?? '' }}</p>
            <h1>{{ $story['title'] ?? '' }}</h1>
            @foreach(($story['paragraphs'] ?? []) as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach
        </section>
    @endforeach
</body>
</html>
