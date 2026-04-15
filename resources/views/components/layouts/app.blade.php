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
        mobileOpen: false,
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

    <nav aria-label="Primary" class="border-b border-border">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">

            {{-- Logo + desktop links --}}
            <div class="flex items-center gap-6">
                <a href="{{ route('home') }}" class="font-display text-lg font-semibold tracking-tight hover:opacity-80 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-border-focus)] rounded-sm">
                    CronosPulse
                </a>
                <div class="hidden items-center gap-1 sm:flex">
                    <x-nav-link href="{{ route('home') }}" :active="request()->routeIs('home')">Home</x-nav-link>
                    <x-nav-link href="{{ route('about') }}" :active="request()->routeIs('about')">About</x-nav-link>
                </div>
            </div>

            {{-- Right side: theme toggle + auth + mobile hamburger --}}
            <div class="flex items-center gap-2">

                <x-theme-toggle />

                @auth
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-button type="submit" variant="ghost" class="hidden sm:inline-flex">Sign out</x-button>
                    </form>
                @else
                    <x-nav-link href="{{ route('login') }}" :active="request()->routeIs('login')" class="hidden sm:inline-flex">Sign in</x-nav-link>
                @endauth

                {{-- Mobile hamburger --}}
                <button
                    @click="mobileOpen = !mobileOpen"
                    :aria-expanded="mobileOpen"
                    aria-controls="mobile-nav"
                    :aria-label="mobileOpen ? 'Close menu' : 'Open menu'"
                    class="rounded-md p-2 text-muted transition-colors hover:bg-surface-hover hover:text-text focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-border-focus)] sm:hidden"
                >
                    <svg x-show="!mobileOpen" xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg x-show="mobileOpen" xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Mobile menu --}}
        <div id="mobile-nav" x-show="mobileOpen" x-collapse class="border-t border-border sm:hidden">
            <div class="space-y-1 px-4 pb-4 pt-2">
                <x-responsive-nav-link href="{{ route('home') }}" :active="request()->routeIs('home')">Home</x-responsive-nav-link>
                <x-responsive-nav-link href="{{ route('about') }}" :active="request()->routeIs('about')">About</x-responsive-nav-link>
                @auth
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-responsive-nav-link href="#" onclick="this.closest('form').submit()">Sign out</x-responsive-nav-link>
                    </form>
                @else
                    <x-responsive-nav-link href="{{ route('login') }}" :active="request()->routeIs('login')">Sign in</x-responsive-nav-link>
                @endauth
            </div>
        </div>
    </nav>

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