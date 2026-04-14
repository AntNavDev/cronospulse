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
    <meta name="description" content="{{ $description ?? 'Real-time geophysical data from USGS — earthquakes, streamflow, and water levels.' }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:ital,wght@0,300;0,400;0,500;1,300;1,400;1,500&family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body
    class="min-h-screen bg-bg text-text antialiased"
    x-data="{
        dark: localStorage.getItem('theme') === 'dark',
        mobileOpen: false,
        toggle() {
            this.dark = !this.dark;
            localStorage.setItem('theme', this.dark ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', this.dark ? 'dark' : '');
        },
    }"
>

    <nav class="border-b border-border">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">

            {{-- Logo + desktop links --}}
            <div class="flex items-center gap-6">
                <a href="{{ route('home') }}" class="font-display text-lg font-semibold tracking-tight hover:opacity-80">
                    CronosPulse
                </a>
                <div class="hidden items-center gap-1 sm:flex">
                    <x-nav-link href="{{ route('home') }}" :active="request()->routeIs('home')">Home</x-nav-link>
                    <x-nav-link href="{{ route('about') }}" :active="request()->routeIs('about')">About</x-nav-link>
                </div>
            </div>

            {{-- Right side: theme toggle + auth + mobile hamburger --}}
            <div class="flex items-center gap-2">

                {{-- Theme toggle --}}
                <button
                    @click="toggle()"
                    class="rounded-md p-2 text-muted transition-colors hover:bg-surface-hover hover:text-text"
                    :aria-label="dark ? 'Switch to light mode' : 'Switch to dark mode'"
                >
                    {{-- Sun icon — shown in dark mode --}}
                    <svg x-show="dark" xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m8.66-9H20M4 12H3m15.36-6.36-.71.71M6.34 17.66l-.71.71M17.66 17.66l.71.71M6.34 6.34l-.71-.71M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    {{-- Moon icon — shown in light mode --}}
                    <svg x-show="!dark" xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z" />
                    </svg>
                </button>

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
                    class="rounded-md p-2 text-muted transition-colors hover:bg-surface-hover hover:text-text sm:hidden"
                    :aria-label="mobileOpen ? 'Close menu' : 'Open menu'"
                >
                    <svg x-show="!mobileOpen" xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg x-show="mobileOpen" xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Mobile menu --}}
        <div x-show="mobileOpen" x-collapse class="border-t border-border sm:hidden">
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

    <main class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        {{ $slot }}
    </main>

    <footer class="border-t border-border">
        <div class="mx-auto max-w-7xl px-4 py-6 text-center text-sm text-muted sm:px-6 lg:px-8">
            &copy; {{ date('Y') }} CronosPulse. Data sourced from the
            <a href="https://www.usgs.gov" target="_blank" rel="noopener noreferrer" class="text-[var(--color-text-link)] underline hover:text-[var(--color-text-link-hover)]">
                U.S. Geological Survey
            </a>.
        </div>
    </footer>

    @livewireScripts
</body>
</html>
