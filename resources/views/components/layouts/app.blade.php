<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    x-data="{
        dark: localStorage.getItem('theme') === 'dark' ||
              (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches),
        toggleDark() {
            this.dark = !this.dark;
            localStorage.setItem('theme', this.dark ? 'dark' : 'light');
        }
    }"
    :class="{ 'dark': dark }"
>
<head>
    {{-- Prevent flash of wrong theme before Alpine initialises --}}
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name') }}</title>
    <meta name="description" content="{{ $description ?? 'Real-time geophysical data from USGS — earthquakes, streamflow, and water levels.' }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-white text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100">

    <nav class="border-b border-gray-200 dark:border-gray-800">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-6">
                <a href="{{ route('home') }}" class="text-lg font-semibold tracking-tight hover:opacity-80">
                    CronosPulse
                </a>
                <div class="hidden items-center gap-4 text-sm sm:flex">
                    <a href="{{ route('home') }}" class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">
                        Home
                    </a>
                    <a href="{{ route('about') }}" class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">
                        About
                    </a>
                </div>
            </div>

            <button
                @click="toggleDark()"
                class="rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                :aria-label="dark ? 'Switch to light mode' : 'Switch to dark mode'"
            >
                {{-- Sun icon (shown in dark mode) --}}
                <svg x-show="dark" xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m8.66-9H20M4 12H3m15.36-6.36-.71.71M6.34 17.66l-.71.71M17.66 17.66l.71.71M6.34 6.34l-.71-.71M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
                {{-- Moon icon (shown in light mode) --}}
                <svg x-show="!dark" xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z" />
                </svg>
            </button>
        </div>
    </nav>

    <main class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        {{ $slot }}
    </main>

    <footer class="border-t border-gray-200 dark:border-gray-800">
        <div class="mx-auto max-w-7xl px-4 py-6 text-center text-sm text-gray-500 sm:px-6 lg:px-8 dark:text-gray-400">
            &copy; {{ date('Y') }} CronosPulse. Data sourced from the
            <a href="https://www.usgs.gov" target="_blank" rel="noopener noreferrer" class="underline hover:text-gray-900 dark:hover:text-gray-100">
                U.S. Geological Survey
            </a>.
        </div>
    </footer>

    @livewireScripts
</body>
</html>