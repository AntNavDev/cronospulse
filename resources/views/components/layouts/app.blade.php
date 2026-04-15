<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    {{-- Prevent flash of wrong theme before Alpine initialises.
         Light is default (:root). Dark sets data-theme="dark". --}}
    <script>
        if (localStorage.theme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    </script>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- SEO — each page injects <x-seo> via <x-slot:seo>; falls back to bare app name --}}
    @if ($seo->isEmpty())
        <x-seo :title="$title ?? null" />
    @else
        {{ $seo }}
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body
    class="min-h-screen bg-bg text-text antialiased"
    x-data="{
        theme: localStorage.getItem('theme') || 'light',
        init() {
            this.$watch('theme', val => {
                localStorage.setItem('theme', val);
                document.documentElement.setAttribute('data-theme', val === 'dark' ? 'dark' : '');
            });
            document.documentElement.setAttribute('data-theme', this.theme === 'dark' ? 'dark' : '');
        },
        toggle() { this.theme = this.theme === 'light' ? 'dark' : 'light'; },
    }"
>

    <x-primary-nav />

    <main id="main-content" class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        {{ $slot }}
    </main>

    <footer class="border-t border-border">
        <div class="mx-auto max-w-7xl px-4 py-6 text-center text-sm text-muted sm:px-6 lg:px-8">
            &copy; {{ date('Y') }} CronosPulse. Data sourced from the
            <a href="https://www.usgs.gov" target="_blank" rel="noopener noreferrer" class="text-[var(--color-text-link)] underline hover:text-[var(--color-text-link-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-border-focus)] rounded-sm">
                U.S. Geological Survey
            </a>.
        </div>
    </footer>

    @livewireScripts
</body>
</html>