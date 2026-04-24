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

**Run Pint after every PHP file you write or edit, and again before every commit:**

```bash
./vendor/bin/sail php ./vendor/bin/pint
```

Do not wait until commit time — run Pint immediately after each file is written so formatting issues are caught before moving on.

Key enforced rules beyond PSR-12 baseline: `declare_strict_types`, `no_unused_imports`, `ordered_imports` (alpha), `single_quote`, trailing commas in multi-line arrays/arguments, `single_blank_line_at_eof`.

This applies to **all** PHP files including test classes — every file must end with a single newline.

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

MySQL is forwarded to `127.0.0.1:3306` for IDE database connections.

## Authentication Model

The site is **public by default**. Authentication is optional and unlocks additional features — never gate data viewing behind a login wall.

**Route grouping in `routes/web.php`:**
- No middleware → publicly accessible to everyone
- `middleware('guest')` → login/register forms, redirect away if already logged in
- `middleware('auth')` → strictly requires authentication (dashboard, logout, user-specific actions)

**Auth-aware components:** For features that are enhanced when logged in (e.g. recent lookups, saved stations), check `auth()->check()` or `auth()->user()` inside the Livewire component or view — do not apply `auth` middleware to the route itself.

```php
// In a Livewire component or Blade view
@auth
    {{-- Show "Save this station" button --}}
@endauth
```

## Form Component Standards

**Raw HTML form elements are forbidden in Blade templates.** Every form control must use the corresponding Blade component so visual changes can be made in one place.

### Available components

| Element | Component | Notes |
|---|---|---|
| `<input type="text/number/…">` | `<x-input>` | Accepts all input attributes; `type` prop defaults to `'text'` |
| `<input type="radio">` + label | `<x-radio>` | Wraps input in a label — pass text in the slot |
| `<input type="checkbox">` + label | `<x-checkbox>` | Wraps input in a label — pass text in the slot |
| `<select>` | `<x-select>` | Accepts `disabled`; pass `class="w-full"` to stretch |
| `<button>` | `<x-button>` | See variants and sizes below |
| Form field label | `<x-input-label>` | Use `compact` prop for xs/uppercase section labels |
| Field error message | `<x-input-error>` | |
| Search input with icon | `<x-search-input>` | |

### `<x-button>` variants and sizes

```blade
{{-- Variants --}}
<x-button variant="primary">Save</x-button>          {{-- solid accent, default --}}
<x-button variant="secondary">Cancel</x-button>      {{-- bordered, surface-raised --}}
<x-button variant="danger">Delete</x-button>         {{-- solid red --}}
<x-button variant="success">Confirm</x-button>       {{-- solid green --}}
<x-button variant="ghost">Close</x-button>           {{-- transparent + hover bg --}}
<x-button variant="link">Reset map</x-button>        {{-- accent text, no shape --}}
<x-button variant="muted-link">← Back</x-button>    {{-- muted text, no shape --}}

{{-- Sizes (ignored by link / muted-link) --}}
<x-button size="sm">+ Save</x-button>   {{-- px-2.5 py-1 text-xs --}}
<x-button size="md">Search</x-button>  {{-- px-4 py-2 text-sm (default) --}}
<x-button size="lg">Submit</x-button>  {{-- px-5 py-2.5 text-base --}}

{{-- link/muted-link inherit font size from context; pass class="text-xs" when needed --}}
<x-button variant="link" class="text-xs" @click="...">Reset map</x-button>
```

### `<x-input-label>` compact variant

```blade
{{-- Standard form field label (text-sm text-text) --}}
<x-input-label for="email" class="mb-1.5">Email</x-input-label>

{{-- Compact section label (text-xs uppercase muted) — for map panel headers, etc. --}}
<x-input-label for="state" compact class="mb-1.5">State</x-input-label>
```

### Documented exceptions where raw `<button>` is acceptable

These patterns cannot cleanly be expressed as `<x-button>` without compromising layout:

- **Table sort buttons** — `<button>` inside `<th>` elements; structural table controls with flex-aligned sort arrows
- **Interactive card-row buttons** — full-width `w-full text-left` buttons styled as list item rows (e.g. flood alert list rows)
- **Icon-only action buttons** — `p-1` square buttons containing only an SVG icon (e.g. dashboard delete/remove buttons)

Use `<a>` (not `<x-button>`) for links that navigate — `<x-button>` always renders a `<button>` element.

### Alpine bindings on Blade components

When passing Alpine reactive bindings to a Blade component, use the full `x-bind:attr` form — **never the `:attr` shorthand**. Blade intercepts `:attr` and evaluates it as PHP, which throws "Undefined constant" for Alpine JS variables.

```blade
{{-- Wrong — Blade evaluates "unit === 'km'" as PHP --}}
<x-radio :checked="unit === 'km'">Kilometers</x-radio>

{{-- Correct — Blade ignores x-bind:*, Alpine picks it up --}}
<x-radio x-bind:checked="unit === 'km'">Kilometers</x-radio>
```

This applies to any Alpine binding (`:disabled`, `:class`, `:value`, etc.) passed as a Blade component attribute.

Similarly, **never use `@disabled(expr)` inside a Blade component tag** — it is compiled as a PHP control structure, which corrupts the component attribute list and causes parse errors. Use `:disabled="$phpVar"` instead (valid for PHP/Livewire variables):

```blade
{{-- Wrong — @disabled inside a component tag breaks compilation --}}
<x-button @disabled($listPage <= 1)>...</x-button>

{{-- Correct — :disabled with a PHP expression --}}
<x-button :disabled="$listPage <= 1">...</x-button>
```

## Documentation

Whenever a migration is created or modified, or a model is added or changed, update `docs/data-model.md` to reflect the current schema — columns, types, relationships, and any index or constraint changes.

Whenever architecture is introduced or changed — new layers, new patterns, new conventions, new config files, new directory purposes — update the relevant `CLAUDE.md` file immediately. If no `CLAUDE.md` exists for that directory yet, create one. The root `CLAUDE.md` folder structure table must always reflect the current state of the project.

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
| `app/Api/` | Raw API client classes (`USGSEarthquake`, `USGSVolcano`, `USGSWaterServices`) extending `ApiConnection` |
| `app/Api/Queries/` | Fluent query builder classes — one per API endpoint |
| `app/Data/` | Readonly DTO classes — typed value objects returned by service classes |
| `app/Services/` | Application service classes that wrap API clients, parse responses into DTOs, and handle caching |
| `app/Livewire/` | All Livewire component classes |
| `app/Livewire/Pages/` | Full-page Livewire components mounted directly to routes |
| `app/Livewire/Hydro/` | Sub-components embedded in HydroWatch (`StreamGauge`, `FloodWatch`) |
| `app/Http/Controllers/` | Thin controllers; prefer Livewire full-page components for interactive pages |
| `resources/views/livewire/` | Blade views for Livewire components (mirror the class namespace) |
| `resources/js/app.js` | Alpine.js component registrations and JS entry point |
| `resources/css/app.css` | Tailwind entry point — all theme tokens and color variables live here |
| `config/api.php` | Base URLs for all external APIs — reads from `.env` |

See `app/Api/CLAUDE.md` and `app/Services/CLAUDE.md` for layer-specific conventions.

## Theme System

This project uses a CSS custom property theme with Tailwind v4.

### How it works

- All colors are defined as CSS variables in `resources/css/app.css`
- `:root` is the **light** theme (default — no attribute needed)
- `[data-theme="dark"]` overrides to the dark theme
- Alpine.js on `<body>` manages the toggle via a `theme` string and persists to `localStorage`
- Tailwind v4 exposes the variables as utility classes via `@theme inline` in `app.css`

### Rules for Blade and Livewire templates

- **Never hardcode hex values.** Always use Tailwind utility aliases or CSS variables directly.
- **Prefer Tailwind utilities** (`bg-surface`, `text-muted`, `border-border`) for standard cases.
- **Use `[var(--color-...)]` arbitrary values** for CSS variables not covered by the Tailwind aliases (e.g. badge colors in dedicated components).
- **Use inline styles** (`style="color: var(--color-text-link)"`) for one-off ad-hoc usage of non-aliased variables in templates.

### Available Tailwind utility aliases

| Utility | CSS variable |
|---|---|
| `bg-bg` | `--color-bg` |
| `bg-surface` | `--color-surface` |
| `bg-surface-raised` | `--color-surface-raised` |
| `bg-surface-sunken` | `--color-surface-sunken` |
| `bg-surface-hover` | `--color-surface-hover` |
| `bg-surface-active` | `--color-surface-active` |
| `border-border` | `--color-border` |
| `text-text` | `--color-text` |
| `text-muted` | `--color-text-muted` |
| `bg-accent` / `text-accent` | `--color-accent` |
| `bg-accent-hover` | `--color-accent-hover` |
| `bg-accent-subtle` | `--color-accent-subtle` |
| `bg-accent-muted` | `--color-accent-muted` |
| `bg-secondary` / `text-secondary` | `--color-accent-secondary` |
| `text-success` / `bg-success` | `--color-success` |
| `text-danger` / `bg-danger` | `--color-danger` |
| `text-warning` / `bg-warning` | `--color-warning` |
| `text-info` / `bg-info` | `--color-info` |

### USGS-specific badge variables

Use `<x-label variant="eq">` for event type badges. For ad-hoc one-off usage, apply inline styles directly — both themes are covered automatically.

| Variable group | Purpose |
|---|---|
| `--color-badge-eq-bg/text/border` | Earthquake events |
| `--color-badge-flood-bg/text/border` | Flood / streamflow alerts |
| `--color-badge-vol-bg/text/border` | Volcano monitoring |
| `--color-badge-geo-bg/text/border` | Landslide / geologic hazard |

Ad-hoc example:

```blade
<span style="
    background: var(--color-badge-eq-bg);
    color: var(--color-badge-eq-text);
    border: 1px solid var(--color-badge-eq-border);
" class="rounded-full px-2 py-0.5 text-xs font-medium">
    M4+
</span>
```

### Skeleton loading states

```blade
<div class="skeleton h-4 w-32"></div>
```

### Adding a new color

1. Add to `:root` in `app.css` (light value)
2. Add to `[data-theme="dark"]` in `app.css` (dark value)
3. If you want a Tailwind utility class, add an entry to `@theme inline` in `app.css`
4. Document it in this file

### Swapping the entire palette

All color values are isolated to the `:root` and `[data-theme="dark"]` blocks in `app.css`. Variable names are stable — only the hex values need to change. No Blade or Livewire files need to be touched when swapping palettes.

## Deployment

Production runs on a DigitalOcean droplet managed with Docker Compose. Traefik handles inbound routing. See `CLAUDE.local.md` for server-specific details (container names, network topology, Traefik setup).

**Key files:**

| File | Purpose |
|---|---|
| `docker-compose.prod.yml` | Production Compose config (app, scheduler, nginx, mysql, redis) |
| `docker/php/Dockerfile` | PHP-FPM production image |
| `docker/nginx/cronospulse.conf` | Nginx vhost config (gzip, try_files, PHP-FPM upstream) |
| `.env.production.example` | All required env vars with comments — copy to `.env` on the server |
| `deploy.sh` | One-shot deploy script (run from the project root on the droplet) |

**First-time server setup:**
1. See `CLAUDE.local.md` for network and Traefik setup steps
2. Copy `.env.production.example` to `.env` and fill in all values
3. `docker compose -f docker-compose.prod.yml up -d --build`

**Deploying updates:**

```bash
bash deploy.sh
```

The script:
1. `git pull` — fetches latest code on the current branch
2. `composer install --no-dev --optimize-autoloader` — inside the running app container
3. `php artisan migrate --force` — runs pending migrations
4. `php artisan config:cache && route:cache && view:cache` — warms the caches
5. `npm ci && npm run build` — rebuilds front-end assets inside the app container
6. `php artisan storage:link` — ensures the public storage symlink exists
7. `docker compose restart app` — reloads PHP-FPM with the new code
8. Prints a timestamped "Deploy complete" message
