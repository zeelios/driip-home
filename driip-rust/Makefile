# ── Driip Rust — Dev Makefile ─────────────────────────────────────────────────
# Usage:
#   make dev        → start DB + Redis, run API locally with hot-reload
#   make db         → start DB + Redis only
#   make migrate    → run all pending migrations
#   make prepare    → generate sqlx offline cache (needed after schema changes)
#   make stop       → stop Docker services
#   make reset-db   → drop + recreate DB (destructive!)
#   make logs       → tail Docker service logs

DB_URL=postgres://driip:driip_dev@localhost:5432/driip

.PHONY: dev db migrate prepare stop reset-db logs install-tools

## Start DB + Redis, run API locally with cargo-watch
dev: db migrate prepare
	DATABASE_URL=$(DB_URL) cargo watch -x run

## Start only DB + Redis in background
db:
	docker compose up -d db redis
	@echo "Waiting for Postgres to be ready..."
	@until docker compose exec -T db pg_isready -U driip > /dev/null 2>&1; do sleep 1; done
	@echo "✓ Postgres ready"

## Run all migrations
migrate:
	DATABASE_URL=$(DB_URL) sqlx migrate run --source ./migrations

## Generate sqlx offline query cache (.sqlx/)
prepare:
	DATABASE_URL=$(DB_URL) cargo sqlx prepare

## Stop all Docker services
stop:
	docker compose down

## Fully reset DB (DESTRUCTIVE)
reset-db:
	docker compose down -v
	docker compose up -d db
	@until docker compose exec -T db pg_isready -U driip > /dev/null 2>&1; do sleep 2; done
	DATABASE_URL=$(DB_URL) sqlx migrate run --source ./migrations
	@echo "✓ DB reset complete"

## Tail logs
logs:
	docker compose logs -f

## Install required local tools (sqlx-cli, cargo-watch)
install-tools:
	cargo install sqlx-cli --no-default-features --features postgres
	cargo install cargo-watch
