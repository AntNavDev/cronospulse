<x-layouts.guest>
    <p>Welcome, {{ Auth::user()->name }}</p>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Log out</button>
    </form>
</x-layouts.guest>
