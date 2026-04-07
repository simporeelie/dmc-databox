<!DOCTYPE html>
<html class="h-full" lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-kt-theme="true" data-kt-theme-mode="light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

        <title inertia>{{ config('app.name', 'DMC DataBox') }}</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet"/>

        <!-- Metronic -->
        <link href="/metronic/vendors/keenicons/styles.bundle.css" rel="stylesheet"/>
        <link href="/metronic/css/styles.css" rel="stylesheet"/>

        <!-- Police principale -->
        <style>
            :root { --font-sans: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif; }
            body { font-family: var(--font-sans); }
        </style>

        <!-- Inertia / Vite -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead

        <!-- Theme mode init (avant render) -->
        <script>
            const defaultThemeMode = 'light';
            let themeMode;
            if (localStorage.getItem('kt-theme')) {
                themeMode = localStorage.getItem('kt-theme');
            } else {
                themeMode = defaultThemeMode;
            }
            if (themeMode === 'system') {
                themeMode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.classList.add(themeMode);
        </script>
    </head>
    <body class="antialiased flex h-full text-base text-foreground bg-background">
        @inertia

        <!-- Metronic Core JS -->
        <script src="/metronic/js/core.bundle.js"></script>
    </body>
</html>
