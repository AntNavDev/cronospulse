# CronosPulse — CLAUDE.md

## Project Overview

CronosPulse (`cronospulse.com`) is a Laravel application that consumes the USGS (United States Geological Survey) API to surface real-time and historical geophysical data — earthquakes, water levels, streamflow, and related datasets. The site visualises this data with interactive charts and maps for public use.

## Tech Stack

- **Runtime:** PHP 8.5 (via Laravel Sail / Ubuntu 24.04 container)
- **Framework:** Laravel 13
- **UI components:** Livewire 4 + Alpine.js
- **CSS:** Tailwind CSS v4 (via `@tailwindcss/vite` plugin, no PostCSS config)
- **Build tool:** Vite 8
- **Charts:** Chart.js ^4.4
- **Maps:** Leaflet.js
- **Database:** MySQL 8.4
- **Cache / Queue:** Redis
- **Testing:** PHPUnit 12
- **Linting:** Laravel Pint (PSR-12)

## Code Style — PSR-12 / Pint

All PHP code must conform to PSR-12. Pint is configured in `pint.json` at the project root.

**Run Pint before every commit:**

```bash
./vendor/bin/sail php ./vendor/bin/pint
```

Key enforced rules beyond PSR-12 baseline: `declare_strict_types`, `no_unused_imports`, `ordered_imports` (alpha), `single_quote`, trailing commas in multi-line arrays/arguments.

## Sail Commands Cheatsheet

```bash
# Start containers in the background
./vendor/bin/sail up -d

# Stop containers
./vendor/bin/sail down

# Run an Artisan command
./vendor/bin/sail artisan <command>

# Run Composer
./vendor/bin/sail composer <command>

# Run npm
./vendor/bin/sail npm <command>

# Open a shell inside the app container
./vendor/bin/sail shell

# Run tests
./vendor/bin/sail php artisan test
```

The app container is named `cronospulse_app`. MySQL is forwarded to `127.0.0.1:3306`.

## Documentation

Whenever a migration is created or modified, or a model is added or changed, update `docs/data-model.md` to reflect the current schema — columns, types, relationships, and any index or constraint changes.

## Testing

Write unit tests whenever new functionality is added — services, models, controllers, and any non-trivial logic. Use PHPUnit 12 via Artisan:

```bash
./vendor/bin/sail php artisan test
```

- Place feature tests in `tests/Feature/` and unit tests in `tests/Unit/`.
- Use `RefreshDatabase` for tests that touch the database.
- Test the happy path and key failure cases (validation errors, auth guards, missing records).

## Folder Structure Conventions

| Path | Purpose |
|---|---|
| `app/Livewire/` | All Livewire component classes |
| `resources/views/livewire/` | Blade views for Livewire components (mirror the class namespace, e.g. `app/Livewire/Earthquakes/Map.php` → `resources/views/livewire/earthquakes/map.blade.php`) |
| `resources/js/app.js` | Alpine.js component registrations and JS entry point |
| `resources/css/app.css` | Tailwind entry point — add custom `@theme` overrides here |
| `app/Services/` | Service classes for USGS API calls and data transformation |
| `app/Http/Controllers/` | Thin controllers; prefer Livewire full-page components for interactive pages |
