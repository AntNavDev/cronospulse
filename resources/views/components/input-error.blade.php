{{--
    Validation error message displayed beneath a form field.

    Props:
        messages — string or array of error messages (typically from $errors->get('field'))
--}}
@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'mt-1 space-y-0.5 text-sm text-[var(--color-danger)]']) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif