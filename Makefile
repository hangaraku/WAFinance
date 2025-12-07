# ===========================================
# Cashfloo Docker Makefile
# ===========================================

.PHONY: help build up down restart logs shell migrate fresh seed test clean

# Default target
help:
	@echo "Cashfloo Docker Commands"
	@echo "========================"
	@echo ""
	@echo "Production:"
	@echo "  make build          Build production Docker image"
	@echo "  make up             Start all containers"
	@echo "  make down           Stop all containers"
	@echo "  make restart        Restart all containers"
	@echo "  make logs           View container logs"
	@echo "  make shell          Open shell in app container"
	@echo ""
	@echo "Database:"
	@echo "  make migrate        Run database migrations"
	@echo "  make fresh          Fresh migration (drops all tables)"
	@echo "  make seed           Run database seeders"
	@echo ""
	@echo "Maintenance:"
	@echo "  make cache          Clear and rebuild all caches"
	@echo "  make test           Run tests"
	@echo "  make clean          Remove all containers and volumes"
	@echo ""
	@echo "Queue (optional):"
	@echo "  make up-queue       Start with queue worker"
	@echo ""

# ===========================================
# Production Commands
# ===========================================

# Build production image
build:
	docker compose build --no-cache

# Start containers
up:
	docker compose up -d

# Start with queue worker
up-queue:
	docker compose --profile with-queue up -d

# Stop containers
down:
	docker compose down

# Restart containers
restart:
	docker compose restart

# View logs
logs:
	docker compose logs -f

# View app logs only
logs-app:
	docker compose logs -f app

# Open shell in app container
shell:
	docker compose exec app sh

# ===========================================
# Database Commands
# ===========================================

# Run migrations
migrate:
	docker compose exec app php artisan migrate --force

# Fresh migration
fresh:
	docker compose exec app php artisan migrate:fresh --force

# Run seeders
seed:
	docker compose exec app php artisan db:seed --force

# ===========================================
# Maintenance Commands
# ===========================================

# Clear and rebuild caches
cache:
	docker compose exec app php artisan config:cache
	docker compose exec app php artisan route:cache
	docker compose exec app php artisan view:cache

# Clear all caches
cache-clear:
	docker compose exec app php artisan config:clear
	docker compose exec app php artisan route:clear
	docker compose exec app php artisan view:clear
	docker compose exec app php artisan cache:clear

# Run tests
test:
	docker compose exec app php artisan test

# ===========================================
# Cleanup Commands
# ===========================================

# Remove containers and volumes
clean:
	docker compose down -v --remove-orphans
	docker system prune -f

# Remove everything including images
clean-all:
	docker compose down -v --remove-orphans --rmi all
	docker system prune -af

# ===========================================
# Deployment Commands
# ===========================================

# Deploy (build and start)
deploy: build up migrate cache
	@echo "✅ Deployment complete!"

# Quick update (rebuild app only)
update:
	docker compose build app
	docker compose up -d app
	docker compose exec app php artisan migrate --force
	docker compose exec app php artisan config:cache
	docker compose exec app php artisan route:cache
	docker compose exec app php artisan view:cache
	@echo "✅ Update complete!"
