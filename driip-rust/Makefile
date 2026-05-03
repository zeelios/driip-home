# ── Driip Rust — Dev Makefile ─────────────────────────────────────────────────
# See HELP.md for full command reference.

DB_URL=postgres://driip:driip_dev@localhost:5432/driip

.PHONY: dev db migrate prepare stop reset-db migrate-fresh fresh logs \
        install-tools seed seed-admin seed-demo build build-lambda test

## Start DB + Redis, run API locally with hot-reload
dev: db migrate prepare
	DATABASE_URL=$(DB_URL) cargo watch -x run

## Start only DB + Redis in background
db:
	docker compose up -d db redis
	@echo "Waiting for Postgres to be ready..."
	@until docker compose exec -T db pg_isready -U driip > /dev/null 2>&1; do sleep 1; done
	@echo "✓ Postgres ready"

## Run all pending migrations
migrate:
	DATABASE_URL=$(DB_URL) sqlx migrate run --source ./migrations

## Generate sqlx offline query cache — run after every schema change
prepare:
	DATABASE_URL=$(DB_URL) cargo sqlx prepare

## Seed: admin user only
seed-admin:
	DATABASE_URL=$(DB_URL) cargo run --bin seed_admin

## Seed: full demo data (products, customers, sample orders)
seed-demo:
	DATABASE_URL=$(DB_URL) cargo run --bin seed_demo

## Seed everything (admin + demo)
seed: seed-admin seed-demo

## migrate:fresh — drop all tables, re-run every migration (DB container stays up)
## Equivalent to: php artisan migrate:fresh
migrate-fresh:
	@echo "⚠  Dropping all tables in driip database..."
	DATABASE_URL=$(DB_URL) sqlx database drop -y
	DATABASE_URL=$(DB_URL) sqlx database create
	DATABASE_URL=$(DB_URL) sqlx migrate run --source ./migrations
	DATABASE_URL=$(DB_URL) cargo sqlx prepare
	@echo "✓ migrate:fresh complete"

## fresh — migrate:fresh + seed (most common workflow after schema changes)
## Equivalent to: php artisan migrate:fresh --seed
fresh: migrate-fresh seed

## Stop all Docker services
stop:
	docker compose down

## Fully reset DB (DESTRUCTIVE — drops all data)
reset-db:
	docker compose down -v
	docker compose up -d db
	@until docker compose exec -T db pg_isready -U driip > /dev/null 2>&1; do sleep 2; done
	DATABASE_URL=$(DB_URL) sqlx migrate run --source ./migrations
	@echo "✓ DB reset complete"

## Tail Docker logs
logs:
	docker compose logs -f

## Release build (optimised, stripped binary)
build:
	cargo build --release

## Lambda build (requires cargo-lambda)
build-lambda:
	cargo lambda build --release

## Run tests
test:
	DATABASE_URL=$(DB_URL) cargo test

## Install required local tools
install-tools:
	cargo install sqlx-cli --no-default-features --features postgres
	cargo install cargo-watch
	cargo install cargo-lambda
