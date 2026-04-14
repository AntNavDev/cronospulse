# Livewire Component Conventions

## Naming

- Class names are singular PascalCase: `Home`, `EarthquakeMap`, `StationDetail`.
- Namespace mirrors the directory: `app/Livewire/Pages/Home.php` → `App\Livewire\Pages\Home`.
- Blade views mirror the class path under `resources/views/livewire/`, using lowercase and hyphens:
  `App\Livewire\Pages\EarthquakeMap` → `resources/views/livewire/pages/earthquake-map.blade.php`.

## Directory structure

| Path | Purpose |
|---|---|
| `app/Livewire/Pages/` | Full-page components mounted directly to routes |
| `app/Livewire/` (subdirectories) | Reusable components embedded in pages |

## Full-page components

Full-page components are mounted directly in `routes/web.php` using the class as the route action:

```php
Route::get('/earthquakes', EarthquakeMap::class)->name('earthquakes');
```

They must declare a layout and title using PHP attributes:

```php
#[Layout('components.layouts.app')]
#[Title('Earthquakes — CronosPulse')]
class EarthquakeMap extends Component
{
    public function render(): View
    {
        return view('livewire.pages.earthquake-map');
    }
}
```

## Creating a new page component

1. Create the class at `app/Livewire/Pages/MyPage.php` with the `#[Layout]` and `#[Title]` attributes.
2. Create the view at `resources/views/livewire/pages/my-page.blade.php`. The view root must be a single `<div>`.
3. Register the route in `routes/web.php`.
4. Add a feature test in `tests/Feature/Pages/MyPageTest.php` asserting HTTP 200.

## Rules

- Every public method must have a PSR-12 docblock.
- `declare(strict_types=1)` is required in every file.
- Blade view roots must be a single element (Livewire requirement).
- Do not put business logic in components — delegate to service classes in `app/Services/`.