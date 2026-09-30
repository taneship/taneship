@use('App\Enums\Theme')
@use('Illuminate\Support\Facades\Vite')
@php($theme = $page['props']['theme'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"@if ($theme === Theme::Dark) class="dark"@endif>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @if ($theme === Theme::System)
            <script nonce="{{ Vite::cspNonce() }}">
                if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.classList.add('dark');
                }
            </script>
        @endif

        {{-- The first paint happens before the stylesheet loads: it takes the background of the theme. --}}
        <style nonce="{{ Vite::cspNonce() }}">
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        <x-inertia::head>
            <title>{{ config('app.name') }}</title>
        </x-inertia::head>
    </head>
    <body class="antialiased">
        <x-inertia::app />
    </body>
</html>
