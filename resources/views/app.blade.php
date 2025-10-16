<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- SEO Meta Tags --}}
        <meta name="description" content="{{ $metaDescription ?? 'Explore Ponorogo - Discover amazing places and experiences' }}">
        <meta name="keywords" content="{{ $metaKeywords ?? 'explore, ponorogo, travel, tourism, destinations' }}">
        <meta name="author" content="yogadev">
        <meta name="robots" content="index, follow">
        <link rel="canonical" href="{{ url()->current() }}">

        {{-- Open Graph Meta Tags --}}
        <meta property="og:title" content="{{ $ogTitle ?? config('app.name', 'Laravel') }}">
        <meta property="og:description" content="{{ $ogDescription ?? $metaDescription ?? 'Explore Ponorogo - Discover amazing places and experiences' }}">
        <meta property="og:image" content="{{ $ogImage ?? asset('exploreponorogo.png') }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:type" content="{{ $ogType ?? 'website' }}">
        <meta property="og:site_name" content="exploreponorogo.com">

        {{-- Twitter Card Meta Tags --}}
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $twitterTitle ?? $ogTitle ?? config('app.name', 'Laravel') }}">
        <meta name="twitter:description" content="{{ $twitterDescription ?? $ogDescription ?? $metaDescription ?? 'Explore Ponorogo - Discover amazing places and experiences' }}">
        <meta name="twitter:image" content="{{ $twitterImage ?? $ogImage ?? asset('exploreponorogo.png') }}">

        <link rel="icon" type="image/x-icon" href="{{ asset('exploreponorogo.ico') }}">
        <link rel="apple-touch-icon" href="{{ asset('exploreponorogo.png') }}">
        <title>exploreponorogo.com</title>
        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
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

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
        @routes
        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
