#!/usr/bin/env bash
set -euo pipefail

COMPOSE_FILE="docker-compose.prod.yml"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Starting deploy..."

# Pull latest code
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Pulling latest from git..."
git pull

# Install PHP dependencies (no dev, optimised autoloader)
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Installing Composer dependencies..."
docker compose -f "$COMPOSE_FILE" exec -T app composer install --no-dev --optimize-autoloader

# Run migrations
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Running migrations..."
docker compose -f "$COMPOSE_FILE" exec -T app php artisan migrate --force

# Cache config, routes, views
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Caching config, routes, and views..."
docker compose -f "$COMPOSE_FILE" exec -T app php artisan config:cache
docker compose -f "$COMPOSE_FILE" exec -T app php artisan route:cache
docker compose -f "$COMPOSE_FILE" exec -T app php artisan view:cache

# Build front-end assets
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Building front-end assets..."
npm ci
npm run build

# Symlink storage
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Linking storage..."
docker compose -f "$COMPOSE_FILE" exec -T app php artisan storage:link

# Restart app container to pick up any code changes
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Restarting app container..."
docker compose -f "$COMPOSE_FILE" restart app

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Deploy complete."