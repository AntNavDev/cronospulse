<nav aria-label="Primary" class="border-b border-border" x-data="{ mobileOpen: false }">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">

        {{-- Logo + desktop links --}}
        <div class="flex items-center gap-6">
            <a href="{{ route('home') }}" class="font-display text-lg font-semibold tracking-tight hover:opacity-80 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-border-focus)] rounded-sm">
                CronosPulse
            </a>
            <div class="hidden items-center gap-1 sm:flex">
                <x-nav-link href="{{ route('home') }}" :active="request()->routeIs('home')">Home</x-nav-link>
                <x-nav-link href="{{ route('quake-watch') }}" :active="request()->routeIs('quake-watch')">QuakeWatch</x-nav-link>
                <x-nav-link href="{{ route('volcano-watch') }}" :active="request()->routeIs('volcano-watch')">VolcanoWatch</x-nav-link>
                <x-nav-link href="{{ route('hydro-watch') }}" :active="request()->routeIs('hydro-watch')">HydroWatch</x-nav-link>
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
            <x-responsive-nav-link href="{{ route('quake-watch') }}" :active="request()->routeIs('quake-watch')">QuakeWatch</x-responsive-nav-link>
            <x-responsive-nav-link href="{{ route('volcano-watch') }}" :active="request()->routeIs('volcano-watch')">VolcanoWatch</x-responsive-nav-link>
            <x-responsive-nav-link href="{{ route('hydro-watch') }}" :active="request()->routeIs('hydro-watch')">HydroWatch</x-responsive-nav-link>
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