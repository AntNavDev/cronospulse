<x-layouts.guest>
    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div>
            <label for="username">Username</label>
            <input id="username" type="text" name="username" value="{{ old('username') }}" required autofocus autocomplete="username">
            @error('username') <span>{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="password">Password</label>
            <input id="password" type="password" name="password" required>
            @error('password') <span>{{ $message }}</span> @enderror
        </div>

        <div>
            <label>
                <input type="checkbox" name="remember"> Remember me
            </label>
        </div>

        <button type="submit">Log in</button>

        <a href="{{ route('register') }}">Create an account</a>
    </form>
</x-layouts.guest>
