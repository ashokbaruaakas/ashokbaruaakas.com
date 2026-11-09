<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $letter->title }}</title>
    <style>
        @page { size: A4; margin: 12mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #000; font-family: Arial, Helvetica, sans-serif; }
        header { padding-bottom: 7mm; }
        h1 { margin: 0 0 6px; font-size: 25pt; font-weight: 700; letter-spacing: -0.5px; }
        .title { margin-bottom: 14px; font-size: 11pt; }
        .contact { display: flex; flex-direction: column; gap: 5px; font-size: 8.5pt; }
        .rule { margin: 0 0 16px; border: 0; border-bottom: 2px solid #10b981; }
        .letter { font-size: 10pt; line-height: 1.6; }
        .date { margin-bottom: 14px; }
        .recipient { margin-bottom: 16px; white-space: pre-line; }
        p { margin: 0 0 10px; white-space: pre-line; }
    </style>
</head>
<body>
    <header>
        <h1>{{ $profile?->name }}</h1>
        @if (filled($title))
            <div class="title">{{ $title }}</div>
        @endif
        @if ($contact !== [])
            <div class="contact">
                @foreach ($contact as $label => $value)
                    <span>{{ $label }}: {{ $value }}</span>
                @endforeach
            </div>
        @endif
    </header>

    <hr class="rule">

    <div class="letter">
        <div class="date">{{ $date }}</div>

        @if (filled($letter->recipient))
            <div class="recipient">{{ $letter->recipient }}</div>
        @endif

        @foreach ($letter->paragraphs() as $paragraph)
            <p>{{ $paragraph }}</p>
        @endforeach
    </div>
</body>
</html>
