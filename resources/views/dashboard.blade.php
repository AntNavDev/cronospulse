<x-layouts.guest>
    <p>Welcome, {{ Auth::user()->name }}</p>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <x-button type="submit" variant="secondary">Log out</x-button>
    </form>
</x-layouts.guest>
