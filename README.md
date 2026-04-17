# CronosPulse

A Laravel application that surfaces real-time geophysical data from USGS and the National Weather Service — earthquakes, volcano activity, and active flood alerts — visualised with interactive charts and maps.

Live at [cronospulse.com](https://cronospulse.com).

## Features

- **QuakeWatch** — Real-time and historical seismic events from the USGS ComCat catalogue, with magnitude, depth, PAGER alert level, and felt reports.
- **VolcanoWatch** — Current alert levels and aviation colour codes for all monitored US volcanoes via the USGS Volcano Hazards Program API.
- **HydroWatch** — Active NWS flood watches, warnings, and advisories mapped as GeoJSON polygons by affected zone, filterable by state and severity.

## Tech Stack

- **PHP** / **Laravel**
- **Livewire** + **Alpine.js** for reactive UI
- **Tailwind CSS** via `@tailwindcss/vite`
- **Chart.js** for data visualisation
- **Leaflet.js** for maps
- **MySQL** + **Redis**
- **Vite** for asset bundling
- **PHPUnit** for testing

## Local Development

The project uses **Laravel Sail** for local development.

```bash
# Start containers
./vendor/bin/sail up -d

# Run migrations
./vendor/bin/sail artisan migrate

# Build frontend assets
./vendor/bin/sail npm run dev

# Run tests
./vendor/bin/sail php artisan test

# Run Pint (code style)
./vendor/bin/sail php ./vendor/bin/pint
```

Copy `.env.example` to `.env` and generate an app key before first run:

```bash
cp .env.example .env
./vendor/bin/sail artisan key:generate
```

## Authentication

The site is public by default. Authentication is optional and unlocks additional features — data viewing is never gated behind a login wall.

## Deployment

Production runs on a DigitalOcean droplet with Docker Compose. Traefik handles routing via the external `proxy` network.

```bash
bash deploy.sh
```

The deploy script pulls the latest code, installs dependencies, runs migrations, warms caches, rebuilds frontend assets, and restarts PHP-FPM. See `CLAUDE.md` for full infrastructure details.

## Code Style

All PHP must conform to PSR-12. Run Pint after every file change:

```bash
./vendor/bin/sail php ./vendor/bin/pint
```

## License

Private — all rights reserved.