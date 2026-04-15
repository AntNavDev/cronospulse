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

The app container is named `cronospulse_app`. MySQL is forwarded to `127.0.0.1:3306`.

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
| `resources/css/app.css` | Tailwind entry point — all theme tokens and color variables live here |
| `app/Services/` | Service classes for USGS API calls and data transformation |
| `app/Http/Controllers/` | Thin controllers; prefer Livewire full-page components for interactive pages |

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

Production runs on a DigitalOcean droplet managed with Docker Compose. Traefik routes inbound traffic to `cronospulse_nginx` via the external `proxy` network — see the global CLAUDE.md for the full per-site network topology.

**Key files:**

| File | Purpose |
|---|---|
| `docker-compose.prod.yml` | Production Compose config (app, scheduler, nginx, mysql, redis) |
| `docker/php/Dockerfile` | PHP 8.5-FPM production image |
| `docker/nginx/cronospulse.conf` | Nginx vhost config (gzip, try_files, PHP-FPM upstream) |
| `.env.production.example` | All required env vars with comments — copy to `.env` on the server |
| `deploy.sh` | One-shot deploy script (run from the project root on the droplet) |

**First-time server setup:**
1. Ensure the external `proxy` network exists: `docker network create proxy`
2. Copy `.env.production.example` to `.env` and fill in all values
3. `docker compose -f docker-compose.prod.yml up -d --build`
4. Add a Traefik file provider rule routing `cronospulse.com` → `cronospulse_nginx` on the `proxy` network

**Deploying updates:**

```bash
bash deploy.sh
```

The script:
1. `git pull` — fetches latest code on the current branch
2. `composer install --no-dev --optimize-autoloader` — inside the running app container
3. `php artisan migrate --force` — runs pending migrations
4. `php artisan config:cache && route:cache && view:cache` — warms the caches
5. `npm ci && npm run build` — rebuilds front-end assets on the host
6. `php artisan storage:link` — ensures the public storage symlink exists
7. `docker compose restart app` — reloads PHP-FPM with the new code
8. Prints a timestamped "Deploy complete" message

> **Note:** `npm ci` and `npm run build` run on the host (the droplet). Node.js must be installed on the droplet.
