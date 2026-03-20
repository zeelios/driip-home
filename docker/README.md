# Driip Docker Development Environment

Complete local development environment with PostgreSQL, Redis, Meilisearch, Nginx, and more.

## Services

| Service | Port | Purpose | Access |
|---------|------|---------|--------|
| **Nginx** | 80 | Web server (reverse proxy) | `http://localhost` |
| **PHP-FPM (App)** | 9000 | Laravel application | Internal only |
| **PostgreSQL** | 5432 | Main database | `localhost:5432` |
| **Redis** | 6379 | Cache & queue driver | `localhost:6379` |
| **Meilisearch** | 7700 | Full-text search | `http://localhost:7700` |
| **MailHog** | 1025, 8025 | Email testing (SMTP + Web UI) | `http://localhost:8025` |
| **Minio** | 9000, 9001 | Local S3 (B2 testing) | `http://localhost:9000` & `http://localhost:9001` |
| **Adminer** | 8080 | Database GUI | `http://localhost:8080` |
| **Queue Worker** | N/A | Background jobs (Redis) | Internal, auto-running |
| **Scheduler** | N/A | Cron jobs | Internal, auto-running |

## Quick Start

### 1. Clone and Setup
```bash
cd /path/to/driip
cp backend/.env.docker backend/.env
```

### 2. Start Services
```bash
docker-compose up -d
```

Monitor startup:
```bash
docker-compose logs -f app
```

Wait for "ready to handle connections" message.

### 3. Initialize Laravel
```bash
# Install dependencies
docker exec driip-app composer install

# Generate app key
docker exec driip-app php artisan key:generate

# Run migrations (once DB is created)
docker exec driip-app php artisan migrate

# Seed database (optional)
docker exec driip-app php artisan db:seed
```

### 4. Access the Application
- **API**: `http://localhost/api/...`
- **Adminer (DB)**: `http://localhost:8080`
  - System: PostgreSQL
  - Server: postgres
  - User: driip
  - Password: driip_password
  - Database: driip_dev

- **MailHog (Emails)**: `http://localhost:8025`
- **Minio Console (S3)**: `http://localhost:9001`
  - User: minioadmin
  - Pass: minioadmin

## Common Commands

### View Logs
```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f app
docker-compose logs -f postgres
docker-compose logs -f redis
```

### Laravel Artisan
```bash
docker exec driip-app php artisan {command}

# Examples
docker exec driip-app php artisan tinker
docker exec driip-app php artisan make:model MyModel
docker exec driip-app php artisan migrate:refresh --seed
```

### Database
```bash
# Connect to PostgreSQL
docker exec -it driip-postgres psql -U driip -d driip_dev

# Run migrations
docker exec driip-app php artisan migrate

# Rollback migrations
docker exec driip-app php artisan migrate:rollback

# Seed database
docker exec driip-app php artisan db:seed
```

### Redis
```bash
# Connect to Redis CLI
docker exec -it driip-redis redis-cli

# Check connection (if password set)
docker exec -it driip-redis redis-cli AUTH your_password
docker exec -it driip-redis redis-cli PING
```

### Meilisearch
```bash
# Create/update search indexes
docker exec driip-app php artisan scout:sync-index-settings

# Re-index products
docker exec driip-app php artisan scout:import "App\Domain\Product\Models\ProductVariant"
```

### Queue & Jobs
```bash
# Check queue status (in Redis)
docker exec -it driip-redis redis-cli

# View failed jobs
docker exec driip-app php artisan queue:failed

# Retry failed jobs
docker exec driip-app php artisan queue:retry all
```

### Restart Services
```bash
# Restart all
docker-compose restart

# Restart specific service
docker-compose restart app
docker-compose restart postgres
docker-compose restart redis
```

## Troubleshooting

### Port Already in Use
If port 80, 5432, 6379, etc. are in use:

**Option 1**: Stop conflicting service
```bash
# macOS (for port 80)
sudo lsof -i :80
sudo kill -9 <PID>

# Or change port in docker-compose.yml
# Change "80:80" to "8000:80" for Nginx
```

**Option 2**: Use a different port
```yaml
# In docker-compose.yml, change nginx ports section to:
ports:
  - "8000:80"  # Now access at localhost:8000
```

### Database Connection Error
```bash
# Check if postgres is running
docker-compose ps

# Check logs
docker-compose logs postgres

# Recreate database volume (WARNING: deletes data)
docker volume rm driip_postgres_data
docker-compose down && docker-compose up -d
```

### Slow on macOS/Windows
Docker on macOS/Windows can be slow. Try:
```bash
# Use native volume mounts instead of Docker volumes
# Or limit CPU/memory in Docker Desktop settings
```

### Queue Worker Not Running
```bash
# Check if running
docker-compose ps queue-worker

# Check logs
docker-compose logs queue-worker

# Restart it
docker-compose restart queue-worker
```

### App Container Exiting
```bash
# Check logs
docker-compose logs app

# Common issue: missing vendor folder
docker exec driip-app composer install

# Or rebuild image
docker-compose down
docker-compose build --no-cache app
docker-compose up -d
```

## Development Tips

### Hot Reload/Auto-Reload
The `backend/` directory is mounted as a volume, so code changes auto-reflect without rebuilding:
```bash
# Edit a file in backend/ → changes appear in container immediately
# No need to restart unless you change:
# - Docker configuration
# - composer.json (run composer install again)
# - Environment variables
```

### Using Tinker REPL
```bash
docker exec -it driip-app php artisan tinker

# Now you're in PHP REPL
>>> App\Domain\Customer\Models\Customer::count()
>>> DB::select('select * from customers limit 1')
```

### Testing
```bash
# Run all tests
docker exec driip-app php artisan test

# Run specific test
docker exec driip-app php artisan test tests/Unit/Domain/Order/Actions/CreateOrderActionTest.php

# With coverage
docker exec driip-app php artisan test --coverage
```

### Database Backup
```bash
# Backup PostgreSQL
docker exec driip-postgres pg_dump -U driip -d driip_dev > backup.sql

# Restore from backup
docker exec -i driip-postgres psql -U driip -d driip_dev < backup.sql
```

## Stop Services

```bash
# Stop all containers (data persists in volumes)
docker-compose down

# Stop and remove all volumes (WARNING: deletes all data)
docker-compose down -v

# Stop without removing
docker-compose stop

# Start again after stopping
docker-compose start
```

## Environment Variables

See `backend/.env.docker` for all available variables. To change:

```bash
# Edit backend/.env
# Then restart affected services
docker-compose restart app queue-worker scheduler
```

## Production Readiness

This setup is **for development only**. For production:
- [ ] Use actual Backblaze B2 (not Minio)
- [ ] Use managed PostgreSQL (AWS RDS, etc.)
- [ ] Use managed Redis (AWS ElastiCache, etc.)
- [ ] Use real mail service (SendGrid, etc.)
- [ ] Enable HTTPS/SSL (Let's Encrypt)
- [ ] Set `APP_DEBUG=false`
- [ ] Set strong `APP_KEY`
- [ ] Use proper `.env` secrets management
- [ ] Configure proper logging (CloudWatch, etc.)
- [ ] Set up monitoring & alerting
- [ ] Scale with Kubernetes or Docker Swarm
