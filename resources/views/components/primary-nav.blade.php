<nav aria-label="Primary" class="border-b border-border" x-data="{ mobileOpen: false, dropdownOpen: false }">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">

        {{-- Logo + desktop links --}}
        <div class="flex items-center gap-6">
            <a href="{{ route('home') }}" class="font-display text-lg font-semibold tracking-tight hover:opacity-80 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-border-focus)] rounded-sm">
                CronosPulse
            </a>
            <div class="hidden items-center gap-1 sm:flex">
                <x-nav-link href="{{ route('quake-watch') }}" :active="request()->routeIs('quake-watch')">QuakeWatch</x-nav-link>
                <x-nav-link href="{{ route('volcano-watch') }}" :active="request()->routeIs('volcano-watch')">VolcanoWatch</x-nav-link>
                <x-nav-link href="{{ route('hydro-watch') }}" :active="request()->routeIs('hydro-watch')">HydroWatch</x-nav-link>
                <x-nav-link href="{{ route('about') }}" :active="request()->routeIs('about')">About</x-nav-link>
            </div>
        </div>

        {{-- Right side: theme toggle + auth + mobile hamburger --}}
        <div class="flex items-center gap-2">

            <x-theme-toggle />

            {{-- Desktop: user dropdown or sign-in link --}}
            @auth
                <div class="relative hidden sm:block" @click.outside="dropdownOpen = false">
                    <button
                        type="button"
                        @click="dropdownOpen = !dropdownOpen"
                        :aria-expanded="dropdownOpen"
                        class="flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium text-muted transition-colors hover:bg-surface-hover hover:text-text focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-border-focus)]"
                    >
                        {{ auth()->user()->username }}
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 transition-transform" :class="dropdownOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div
                        x-show="dropdownOpen"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="absolute right-0 z-50 mt-1 w-44 origin-top-right rounded-xl border border-border bg-surface shadow-lg"
                    >
                        <div class="p-1">
                            <a
                                href="{{ route('dashboard') }}"
                                @click="dropdownOpen = false"
                                class="flex w-full items-center rounded-lg px-3 py-2 text-sm text-text transition-colors hover:bg-surface-hover"
                            >
                                Dashboard
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button
                                    type="submit"
                                    class="flex w-full items-center rounded-lg px-3 py-2 text-sm text-muted transition-colors hover:bg-surface-hover hover:text-text"
                                >
                                    Sign out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
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
        <div class="px-4 pb-4 pt-2">
            <div class="space-y-1">
                <x-responsive-nav-link href="{{ route('quake-watch') }}" :active="request()->routeIs('quake-watch')">QuakeWatch</x-responsive-nav-link>
                <x-responsive-nav-link href="{{ route('volcano-watch') }}" :active="request()->routeIs('volcano-watch')">VolcanoWatch</x-responsive-nav-link>
                <x-responsive-nav-link href="{{ route('hydro-watch') }}" :active="request()->routeIs('hydro-watch')">HydroWatch</x-responsive-nav-link>
                <x-responsive-nav-link href="{{ route('about') }}" :active="request()->routeIs('about')">About</x-responsive-nav-link>
            </div>

            {{-- Mobile user section --}}
            @auth
                <div class="mt-3 border-t border-border pt-3">
                    <p class="px-3 pb-1 text-xs font-semibold uppercase tracking-wider text-muted">
                        {{ auth()->user()->username }}
                    </p>
                    <x-responsive-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">Dashboard</x-responsive-nav-link>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-responsive-nav-link href="#" onclick="this.closest('form').submit()">Sign out</x-responsive-nav-link>
                    </form>
                </div>
            @else
                <div class="mt-3 border-t border-border pt-3">
                    <x-responsive-nav-link href="{{ route('login') }}" :active="request()->routeIs('login')">Sign in</x-responsive-nav-link>
                </div>
            @endauth
        </div>
    </div>
</nav>