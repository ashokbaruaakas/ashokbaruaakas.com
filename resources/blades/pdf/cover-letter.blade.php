<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $letter->title }}</title>
    <style>
        @page { size: A4; margin: 22mm 22mm 20mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #1f2328; font: 10.5pt/1.55 "Helvetica Neue", Helvetica, Arial, sans-serif; }
        header { padding-bottom: 14pt; margin-bottom: 22pt; border-bottom: 1px solid #d0d7de; }
        h1 { margin: 0; font-size: 20pt; font-weight: 600; letter-spacing: -0.01em; }
        .headline { margin-top: 2pt; color: #57606a; font-size: 11pt; }
        .contact { margin-top: 8pt; color: #57606a; font-size: 9.5pt; }
        .contact span + span::before { content: "·"; margin: 0 6pt; color: #8c959f; }
        .date { margin-bottom: 16pt; }
        .recipient { margin-bottom: 18pt; white-space: pre-line; }
        p { margin: 0 0 11pt; white-space: pre-line; }
    </style>
</head>
<body>
    <header>
        <h1>{{ $profile?->name }}</h1>
        @if (filled($profile?->headline))
            <div class="headline">{{ $profile->headline }}</div>
        @endif
        @if ($contact !== [])
            <div class="contact">
                @foreach ($contact as $detail)
                    <span>{{ $detail }}</span>
                @endforeach
            </div>
        @endif
    </header>

    <div class="date">{{ $date }}</div>

    @if (filled($letter->recipient))
        <div class="recipient">{{ $letter->recipient }}</div>
    @endif

    @foreach ($letter->paragraphs() as $paragraph)
        <p>{{ $paragraph }}</p>
    @endforeach
</body>
</html>
