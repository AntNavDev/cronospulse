# CronosPulse

A Laravel application that surfaces real-time and historical geophysical data from the USGS (United States Geological Survey) API — earthquakes, water levels, streamflow, and related datasets — visualised with interactive charts and maps.

Live at [cronospulse.com](https://cronospulse.com).

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