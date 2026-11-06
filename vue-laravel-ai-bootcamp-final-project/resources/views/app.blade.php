<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'NOVA AI') }}</title>
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Myanmar:wght@400;600&display=swap" rel="stylesheet">
        <script>
            (function () {
                var theme = localStorage.getItem('theme') === 'dark' ? 'dark' : 'light';

                document.documentElement.setAttribute('data-theme', theme);

                var lang = localStorage.getItem('lang');

                if (lang !== 'en' && lang !== 'my') {
                    lang = (navigator.language || '').toLowerCase().indexOf('my') === 0 ? 'my' : 'en';
                }

                document.documentElement.lang = lang;
            })();
        </script>
        @vite(['resources/css/nova.css', 'resources/js/app.ts'])
        @inertiaHead
    </head>
    <body>
        @inertia
    </body>
</html>
