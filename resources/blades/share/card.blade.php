<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @if ($fonts['heading'])
            @font-face { font-family: 'Card Heading'; font-weight: 700; src: url('{{ $fonts['heading'] }}') format('woff2'); }
        @endif
        @if ($fonts['body'])
            @font-face { font-family: 'Card Body'; font-weight: 500; src: url('{{ $fonts['body'] }}') format('woff2'); }
        @endif

        * { box-sizing: border-box; margin: 0; }

        html, body { width: {{ $width }}px; height: {{ $height }}px; overflow: hidden; }

        body {
            position: relative;
            display: flex;
            flex-direction: column;
            padding: 68px 70px 56px;
            color: #f8fafc;
            font-family: 'Card Body', 'Helvetica Neue', Arial, sans-serif;
            font-weight: 500;
            background:
                radial-gradient(circle at 87% 17%, rgba(16, 185, 129, .2), transparent 330px),
                radial-gradient(circle at 92% 89%, rgba(16, 185, 129, .16), transparent 260px),
                linear-gradient(135deg, #0b1220, #142d36);
        }

        .content { position: relative; display: flex; flex: 1; gap: 48px; align-items: center; min-height: 0; }

        .text { display: flex; flex: 1; flex-direction: column; min-width: 0; }

        .kicker { display: flex; gap: 16px; align-items: center; color: #cbd5e1; font-size: 20px; letter-spacing: 5px; text-transform: uppercase; }

        .monogram {
            display: grid; place-items: center; width: 56px; height: 56px; border: 1px solid rgba(52, 211, 153, .55);
            border-radius: 16px; background: rgba(16, 185, 129, .16); color: #6ee7b7;
            font-family: 'Card Heading', 'Helvetica Neue', Arial, sans-serif; font-size: 24px; font-weight: 700; letter-spacing: 0;
        }

        .accent { width: 72px; height: 5px; margin: 44px 0 34px; border-radius: 3px; background: linear-gradient(90deg, #34d399, #5eead4); }

        h1 {
            font-family: 'Card Heading', 'Helvetica Neue', Arial, sans-serif; font-size: 64px; font-weight: 700; line-height: 1.08;
            display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 3; overflow: hidden;
        }

        h1.medium { font-size: 54px; }

        h1.long { font-size: 46px; }

        .headline {
            margin-top: 18px; color: #6ee7b7; font-size: 34px; line-height: 1.2;
            display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2; overflow: hidden;
        }

        .description {
            margin-top: 22px; color: #cbd5e1; font-size: 24px; line-height: 1.4;
            display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2; overflow: hidden;
        }

        .portrait { position: relative; flex: none; width: 340px; height: 340px; }

        .portrait::before {
            content: ''; position: absolute; inset: -12px; border: 1px dashed rgba(52, 211, 153, .25); border-radius: 50%;
        }

        .portrait img {
            width: 100%; height: 100%; border: 1px solid rgba(52, 211, 153, .22); border-radius: 50%; object-fit: cover;
            background: linear-gradient(180deg, rgba(16, 185, 129, .32), rgba(20, 184, 166, .12) 55%, #0b1220);
        }

        footer {
            position: relative; display: flex; justify-content: space-between; gap: 32px; padding-top: 26px;
            border-top: 1px solid rgba(148, 163, 184, .22); color: #94a3b8; font-size: 20px;
        }
    </style>
</head>
<body>
    <div class="content">
        <div class="text">
            <div class="kicker">
                <span class="monogram">{{ collect(preg_split('/\s+/', trim($card->name)))->filter()->take(2)->map(fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))->implode('') }}</span>
                <span>{{ $card->label ?? $card->name }}</span>
            </div>
            <div class="accent"></div>
            @php($title = $card->label === null ? ($card->headline ?? $card->name) : $card->name)
            <h1 @class(['medium' => mb_strlen($title) > 24 && mb_strlen($title) <= 40, 'long' => mb_strlen($title) > 40])>{{ $title }}</h1>
            @if ($card->label !== null && $card->headline)
                <p class="headline">{{ $card->headline }}</p>
            @endif
            @if ($card->description !== '')
                <p class="description">{{ $card->description }}</p>
            @endif
        </div>
        @if ($portrait)
            <div class="portrait"><img src="{{ $portrait }}" alt=""></div>
        @endif
    </div>
    <footer>
        <span>{{ $card->location }}</span>
        <span>{{ $card->host }}</span>
    </footer>
</body>
</html>
