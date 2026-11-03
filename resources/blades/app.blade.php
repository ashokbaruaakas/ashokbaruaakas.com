<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'dark') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @inject('generalSettings', 'App\Settings\GeneralSettings')
        @if ($generalSettings->tagline)
            <meta name="description" content="{{ $generalSettings->tagline }}">
        @endif

        {{-- Apply dark mode by default, or the user's saved preference --}}
        <script nonce="{{ Vite::cspNonce() }}">
            (function() {
                const appearance = '{{ $appearance ?? "dark" }}';

                if (appearance !== 'light') {
                    document.documentElement.classList.add('dark');
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        @php($media = $page['props']['media'] ?? [])
        @if (isset($media['favicon_ico']) || isset($media['favicon_png']))
            @isset($media['favicon_ico'])
                <link rel="icon" href="{{ $media['favicon_ico'] }}" sizes="any">
            @endisset
            @isset($media['favicon_png'])
                <link rel="icon" href="{{ $media['favicon_png'] }}" sizes="any">
            @endisset
        @else
            <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        @endif
        @isset($media['apple_touch_icon'])
            <link rel="apple-touch-icon" href="{{ $media['apple_touch_icon'] }}">
        @endisset

        @fonts

        @vite(['resources/app.ts', "resources/views/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
