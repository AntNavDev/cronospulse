<x-layouts.guest title="Sign in">
    <form method="POST" action="{{ route('login') }}" class="space-y-5" novalidate>
        @csrf

        <div class="space-y-1">
            <x-input-label for="username" value="Username" />
            <x-input
                id="username"
                name="username"
                type="text"
                :value="old('username')"
                required
                autofocus
                autocomplete="username"
                :class="$errors->has('username') ? 'border-[var(--color-danger)] focus:border-[var(--color-danger)] focus:ring-[var(--color-danger)]' : ''"
                aria-describedby="{{ $errors->has('username') ? 'username-error' : '' }}"
                :aria-invalid="$errors->has('username') ? 'true' : null"
            />
            <x-input-error id="username-error" :messages="$errors->get('username')" role="alert" />
        </div>

        <div class="space-y-1">
            <x-input-label for="password" value="Password" />
            <x-input
                id="password"
                name="password"
                type="password"
                required
                autocomplete="current-password"
                :class="$errors->has('password') ? 'border-[var(--color-danger)] focus:border-[var(--color-danger)] focus:ring-[var(--color-danger)]' : ''"
                aria-describedby="{{ $errors->has('password') ? 'password-error' : '' }}"
                :aria-invalid="$errors->has('password') ? 'true' : null"
            />
            <x-input-error id="password-error" :messages="$errors->get('password')" role="alert" />
        </div>

        <x-checkbox name="remember">Remember me</x-checkbox>

        <x-button type="submit" variant="primary" class="w-full">
            Sign in
        </x-button>

        <p class="text-center text-sm text-muted">
            Don't have an account?
            <a href="{{ route('register') }}" class="font-medium text-[var(--color-text-link)] hover:text-[var(--color-text-link-hover)] underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-border-focus)] rounded-sm">
                Create one
            </a>
        </p>

    </form>
</x-layouts.guest>