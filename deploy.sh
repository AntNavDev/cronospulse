#!/usr/bin/env bash
set -euo pipefail

# ── Flags ──────────────────────────────────────────────────────────────────────
# --build   Force a Docker image rebuild (use after Dockerfile changes)
# --fresh   Drop and re-run all migrations (destructive — prompts for confirmation)
# --seed    Run seeders after migrations
BUILD=false
FRESH=false
SEED=false

for arg in "$@"; do
  case $arg in
    --build) BUILD=true ;;
    --fresh) FRESH=true ;;
    --seed)  SEED=true  ;;
  esac
done

COMPOSE="docker compose -f docker-compose.prod.yml"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Starting deploy..."

# ── Maintenance mode ───────────────────────────────────────────────────────────
# Skip if the app container isn't running yet (first deploy)
if $COMPOSE ps --status running 2>/dev/null | grep -q 'cronospulse_app'; then
  echo "==> Enabling maintenance mode..."
  $COMPOSE exec -T app php artisan down --retry=30 || true
fi

# ── Code ───────────────────────────────────────────────────────────────────────
echo "==> Pulling latest code..."
git pull origin "$(git branch --show-current)"

# ── Docker ─────────────────────────────────────────────────────────────────────
if [ "$BUILD" = true ]; then
  echo "==> Rebuilding Docker images..."
  $COMPOSE build --no-cache
fi

echo "==> Starting containers..."
$COMPOSE up -d

echo "==> Restarting app container to clear OPcache..."
$COMPOSE restart app

# ── Wait for MySQL ─────────────────────────────────────────────────────────────
echo "==> Waiting for MySQL..."
until $COMPOSE exec -T mysql mysqladmin ping -h localhost --silent 2>/dev/null; do
  sleep 2
done
echo "    MySQL ready."

# ── Dependencies & assets ──────────────────────────────────────────────────────
echo "==> Installing Composer dependencies..."
$COMPOSE exec -T app composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Building frontend assets..."
$COMPOSE exec -T app npm ci
$COMPOSE exec -T app npm run build

# ── Database ───────────────────────────────────────────────────────────────────
if [ "$FRESH" = true ]; then
  echo "WARNING: Fresh migration will drop all tables."
  read -p "Are you sure? (yes/no): " confirm
  if [ "$confirm" = "yes" ]; then
    $COMPOSE exec -T app php artisan migrate:fresh --force
  else
    echo "Skipping fresh migration."
  fi
else
  echo "==> Running migrations..."
  $COMPOSE exec -T app php artisan migrate --force
fi

if [ "$SEED" = true ]; then
  echo "==> Running seeders..."
  $COMPOSE exec -T app php artisan db:seed --force
fi

# ── Framework caches ───────────────────────────────────────────────────────────
echo "==> Caching config, routes, views, and events..."
$COMPOSE exec -T app php artisan config:cache
$COMPOSE exec -T app php artisan route:cache
$COMPOSE exec -T app php artisan view:cache
$COMPOSE exec -T app php artisan event:cache

# ── Storage ────────────────────────────────────────────────────────────────────
echo "==> Setting permissions..."
$COMPOSE exec -T app chown -R www-data:www-data storage bootstrap/cache
$COMPOSE exec -T app chmod -R 775 storage bootstrap/cache
$COMPOSE exec -T app php artisan storage:link 2>/dev/null || true

# ── Sitemap ────────────────────────────────────────────────────────────────────
echo "==> Regenerating sitemap..."
$COMPOSE exec -T app php artisan sitemap:generate

# ── Done ───────────────────────────────────────────────────────────────────────
echo "==> Disabling maintenance mode..."
$COMPOSE exec -T app php artisan up

COMMIT=$(git rev-parse --short HEAD)

echo ""
echo "========================================="
echo "Deploy complete!"
echo "Time:   $(date '+%Y-%m-%d %H:%M:%S')"
echo "Commit: $COMMIT"
echo "========================================="