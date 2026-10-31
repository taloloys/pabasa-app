<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $churchName }} — {{ $massTitle }}</title>
    <style>
        @page {
            margin: 24mm 18mm 22mm;
        }

        body {
            color: #1c1917;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
        }

        header {
            border-bottom: 2px solid #78350f;
            margin-bottom: 18pt;
            padding-bottom: 12pt;
            text-align: center;
        }

        h1 {
            color: #451a03;
            font-size: 18pt;
            margin: 0;
        }

        h2 {
            color: #292524;
            font-size: 14pt;
            margin: 8pt 0 2pt;
        }

        h3 {
            border-bottom: 1px solid #d6d3d1;
            color: #78350f;
            font-size: 12pt;
            margin: 18pt 0 8pt;
            padding-bottom: 3pt;
        }

        .meta {
            color: #57534e;
            font-size: 9pt;
            margin: 3pt 0;
        }

        .celebrant {
            margin-top: 12pt;
        }

        .intention {
            margin: 0 0 14pt;
            page-break-inside: avoid;
        }

        .name {
            font-size: 13pt;
            font-weight: bold;
            margin-bottom: 3pt;
        }

        .wording {
            white-space: pre-wrap;
        }

        .pronunciation {
            color: #57534e;
            font-size: 9pt;
            font-style: italic;
            margin: 0 0 4pt;
        }

        .watermark {
            bottom: -12mm;
            color: #78716c;
            font-size: 8pt;
            position: fixed;
            right: 0;
        }
    </style>
</head>
<body>
    <header>
        <h1>{{ $churchName }}</h1>
        <h2>{{ $massTitle }}</h2>
        <p class="meta">{{ $massDateTime }}</p>
        @if ($locationLabel)
            <p class="meta">{{ __('Location') }}: {{ $locationLabel }}</p>
        @endif
        <p class="meta celebrant">{{ __('Celebrant') }}: __________________________________</p>
    </header>

    @php($lastCategory = null)
    @foreach ($entries as $entry)
        @php($category = $entry['category_label'] ?: __('Intentions'))
        @if ($category !== $lastCategory)
            <h3>{{ $category }}</h3>
            @php($lastCategory = $category)
        @endif
        <section class="intention">
            <p class="name">{{ $entry['person_or_family_name'] }}</p>
            @if ($entry['pronunciation_hint'])
                <p class="pronunciation">{{ __('Pronunciation') }}: {{ $entry['pronunciation_hint'] }}</p>
            @endif
            <p class="wording">{{ $entry['wording'] }}</p>
        </section>
    @endforeach

    <div class="watermark">
        {{ __('Generated') }} {{ $generatedAt }} UTC · {{ __('Reading list version') }} {{ $version }}
    </div>
</body>
</html>
