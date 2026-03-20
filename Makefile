.PHONY: help up down restart logs shell bash migrate seed tinker test

help:
	@echo "Driip Docker Commands"
	@echo "===================="
	@echo ""
	@echo "Make targets:"
	@echo "  make up              - Start all Docker containers"
	@echo "  make down            - Stop all Docker containers"
	@echo "  make restart         - Restart all containers"
	@echo "  make logs            - View logs from all services"
	@echo "  make logs-app        - View Laravel app logs"
	@echo "  make logs-db         - View PostgreSQL logs"
	@echo "  make logs-redis      - View Redis logs"
	@echo ""
	@echo "Laravel commands:"
	@echo "  make bash            - Open bash shell in app container"
	@echo "  make tinker          - Open PHP REPL (Tinker)"
	@echo "  make migrate         - Run database migrations"
	@echo "  make seed            - Seed database with test data"
	@echo "  make fresh           - Rollback and re-migrate"
	@echo "  make test            - Run tests"
	@echo ""
	@echo "Setup:"
	@echo "  make setup           - Initial setup (install deps, migrate, seed)"
	@echo ""

up:
	docker-compose up -d
	@echo "✓ Docker containers started"

down:
	docker-compose down
	@echo "✓ Docker containers stopped"

restart:
	docker-compose restart
	@echo "✓ Docker containers restarted"

logs:
	docker-compose logs -f

logs-app:
	docker-compose logs -f app

logs-db:
	docker-compose logs -f postgres

logs-redis:
	docker-compose logs -f redis

logs-queue:
	docker-compose logs -f queue-worker

bash:
	docker exec -it driip-app bash

shell:
	docker exec -it driip-app sh

tinker:
	docker exec -it driip-app php artisan tinker

migrate:
	docker exec driip-app php artisan migrate

migrate-fresh:
	docker exec driip-app php artisan migrate:fresh

seed:
	docker exec driip-app php artisan db:seed

fresh:
	docker exec driip-app php artisan migrate:fresh --seed
	@echo "✓ Database reset and seeded"

test:
	docker exec driip-app php artisan test

test-coverage:
	docker exec driip-app php artisan test --coverage

composer-install:
	docker exec driip-app composer install

composer-update:
	docker exec driip-app composer update

key-generate:
	docker exec driip-app php artisan key:generate

setup: up
	@echo "Waiting for services to be ready..."
	sleep 10
	docker exec driip-app composer install
	docker exec driip-app php artisan key:generate
	docker exec driip-app php artisan migrate
	docker exec driip-app php artisan db:seed
	@echo ""
	@echo "✓ Setup complete!"
	@echo ""
	@echo "Access points:"
	@echo "  API:      http://localhost/api"
	@echo "  Adminer:  http://localhost:8080"
	@echo "  MailHog:  http://localhost:8025"
	@echo "  Minio:    http://localhost:9001"

clean:
	docker-compose down -v
	@echo "✓ All containers and volumes removed"

ps:
	docker-compose ps

build:
	docker-compose build

build-no-cache:
	docker-compose build --no-cache

stats:
	docker stats driip-app driip-postgres driip-redis
