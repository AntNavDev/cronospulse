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
    <title>{{ $title ?? config('app.name') }}</title>

    <link rel="icon" type="image/svg+xml" href="/favicon.svg">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
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

    <main class="flex min-h-[calc(100vh-4rem)] flex-col items-center justify-center px-4 py-12">
        <div class="w-full max-w-sm space-y-6">

            {{-- Card --}}
            <div class="rounded-xl border border-border bg-surface p-8 shadow-sm">
                {{ $slot }}
            </div>

        </div>
    </main>

</body>
</html>