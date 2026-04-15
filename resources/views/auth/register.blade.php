<x-layouts.guest title="Create account">
    <form method="POST" action="{{ route('register') }}" class="space-y-5" novalidate>
        @csrf

        <div class="space-y-1">
            <x-input-label for="name" value="Name" />
            <x-input
                id="name"
                name="name"
                type="text"
                :value="old('name')"
                required
                autofocus
                autocomplete="name"
                :class="$errors->has('name') ? 'border-[var(--color-danger)] focus:border-[var(--color-danger)] focus:ring-[var(--color-danger)]' : ''"
                aria-describedby="{{ $errors->has('name') ? 'name-error' : '' }}"
                :aria-invalid="$errors->has('name') ? 'true' : null"
            />
            <x-input-error id="name-error" :messages="$errors->get('name')" role="alert" />
        </div>

        <div class="space-y-1">
            <x-input-label for="username" value="Username" />
            <x-input
                id="username"
                name="username"
                type="text"
                :value="old('username')"
                required
                autocomplete="username"
                :class="$errors->has('username') ? 'border-[var(--color-danger)] focus:border-[var(--color-danger)] focus:ring-[var(--color-danger)]' : ''"
                aria-describedby="{{ $errors->has('username') ? 'username-error' : '' }}"
                :aria-invalid="$errors->has('username') ? 'true' : null"
            />
            <x-input-error id="username-error" :messages="$errors->get('username')" role="alert" />
        </div>

        <div class="space-y-1">
            <x-input-label for="email" value="Email" />
            <x-input
                id="email"
                name="email"
                type="email"
                :value="old('email')"
                required
                autocomplete="email"
                :class="$errors->has('email') ? 'border-[var(--color-danger)] focus:border-[var(--color-danger)] focus:ring-[var(--color-danger)]' : ''"
                aria-describedby="{{ $errors->has('email') ? 'email-error' : '' }}"
                :aria-invalid="$errors->has('email') ? 'true' : null"
            />
            <x-input-error id="email-error" :messages="$errors->get('email')" role="alert" />
        </div>

        <div class="space-y-1">
            <x-input-label for="password" value="Password" />
            <x-input
                id="password"
                name="password"
                type="password"
                required
                autocomplete="new-password"
                :class="$errors->has('password') ? 'border-[var(--color-danger)] focus:border-[var(--color-danger)] focus:ring-[var(--color-danger)]' : ''"
                aria-describedby="{{ $errors->has('password') ? 'password-error' : '' }}"
                :aria-invalid="$errors->has('password') ? 'true' : null"
            />
            <x-input-error id="password-error" :messages="$errors->get('password')" role="alert" />
        </div>

        <div class="space-y-1">
            <x-input-label for="password_confirmation" value="Confirm Password" />
            <x-input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                required
                autocomplete="new-password"
            />
        </div>

        <x-button type="submit" variant="primary" class="w-full">
            Create account
        </x-button>

        <p class="text-center text-sm text-muted">
            Already have an account?
            <a href="{{ route('login') }}" class="font-medium text-[var(--color-text-link)] hover:text-[var(--color-text-link-hover)] underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-border-focus)] rounded-sm">
                Sign in
            </a>
        </p>

    </form>
</x-layouts.guest>