#!/usr/bin/env bash
set -euo pipefail

# setup-laravel.sh
# Reusable setup script for a cloned Laravel project.
# Usage:
#   ./scripts/setup-laravel.sh [--no-composer] [--no-npm] [--use-acl]
# Run from project root. Use sudo when prompted for chown/chmod.

PROJECT_ROOT=$(pwd)
OWNER=${SUDO_USER:-$USER}
WEBGROUP=www-data
USE_ACL=0
DO_COMPOSER=1
DO_NPM=1

while [[ $# -gt 0 ]]; do
  case "$1" in
    --no-composer) DO_COMPOSER=0; shift ;;
    --no-npm) DO_NPM=0; shift ;;
    --use-acl) USE_ACL=1; shift ;;
    --help) echo "Usage: $0 [--no-composer] [--no-npm] [--use-acl]"; exit 0 ;;
    *) echo "Unknown arg: $1"; exit 1 ;;
  esac
done

echo "Project root: $PROJECT_ROOT"
echo "Owner user: $OWNER, web group: $WEBGROUP"

# 1) Ensure project files are owned by developer user
echo "
1) Setting project ownership to $OWNER:$OWNER (safe for npm/nvm)"
sudo chown -R "$OWNER:$OWNER" "$PROJECT_ROOT"

# 2) Make only Laravel writable dirs writable by webserver
echo "
2) Making storage & bootstrap/cache group-writable by $WEBGROUP"
sudo chown -R "$OWNER:$WEBGROUP" "$PROJECT_ROOT/storage" "$PROJECT_ROOT/bootstrap/cache" || true
sudo find "$PROJECT_ROOT/storage" -type d -exec chmod 775 {} \; || true
sudo find "$PROJECT_ROOT/storage" -type f -exec chmod 664 {} \; || true
sudo find "$PROJECT_ROOT/bootstrap/cache" -type d -exec chmod 775 {} \; || true
sudo find "$PROJECT_ROOT/bootstrap/cache" -type f -exec chmod 664 {} \; || true

# 2b) Optionally add ACL so that owner remains the same but www-data has RWX
if [ "$USE_ACL" -eq 1 ]; then
  if ! command -v setfacl >/dev/null 2>&1; then
    echo "setfacl not found — installing acl package"
    sudo apt-get update && sudo apt-get install -y acl
  fi
  echo "Applying ACLs for user:www-data on storage & bootstrap/cache"
  sudo setfacl -R -m u:$WEBGROUP:rwx "$PROJECT_ROOT/storage" "$PROJECT_ROOT/bootstrap/cache"
  sudo setfacl -R -d -m u:$WEBGROUP:rwx "$PROJECT_ROOT/storage" "$PROJECT_ROOT/bootstrap/cache"
fi

# 3) Ensure public is readable by others so nginx can serve files
echo "
3) Ensuring webroot is readable"
sudo find "$PROJECT_ROOT/public" -type d -exec chmod 755 {} \; || true
sudo find "$PROJECT_ROOT/public" -type f -exec chmod 644 {} \; || true

# 4) Composer install (if requested)
if [ "$DO_COMPOSER" -eq 1 ]; then
  if [ -f composer.json ]; then
    echo "
4) Running composer install (no dev by default)"
    if command -v composer >/dev/null 2>&1; then
      composer install --no-interaction --prefer-dist --optimize-autoloader
    else
      echo "composer not found in PATH. Skipping composer install."
    fi
  else
    echo "composer.json not found — skipping composer step"
  fi
fi

# 5) NPM install and build (as developer user so nvm works)
if [ "$DO_NPM" -eq 1 ]; then
  if [ -f package.json ]; then
    echo "
5) Running npm install and build as $OWNER"
    if [ "$OWNER" != "$USER" ]; then
      echo "Running npm commands as $OWNER using sudo -u"
      sudo -u "$OWNER" bash -lc 'if command -v npm >/dev/null 2>&1; then npm install --no-audit --no-fund; npm run build || true; else echo "npm not found for $OWNER"; fi'
    else
      if command -v npm >/dev/null 2>&1; then
        npm install --no-audit --no-fund
        npm run build || true
      else
        echo "npm not found — skipping npm steps"
      fi
    fi
  else
    echo "package.json not found — skipping npm step"
  fi
fi

# 6) Artisan post-setup
if command -v php >/dev/null 2>&1 && [ -f artisan ]; then
  echo "
6) Running Laravel artisan tasks (key, storage:link, cache clear)"
  php artisan key:generate --ansi || true
  php artisan storage:link || true
  php artisan config:clear || true
  php artisan route:clear || true
  php artisan view:clear || true
else
  echo "php or artisan not available — skip artisan tasks"
fi

# 7) Final note
cat <<EOF

Setup complete. Next suggestions:
- Verify your nginx site block points to: $PROJECT_ROOT/public
- If using the provided wildcard site, add this to /etc/hosts:
  127.0.0.1 yourproject.test
  (or replace yourproject with the folder name)
- To let nginx/PHP-FPM write logs inside /var/log/nginx use the existing defaults (www-data:adm)
- If you prefer ACL approach, re-run script with --use-acl

EOF

exit 0
