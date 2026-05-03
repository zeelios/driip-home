#!/bin/bash
# ── Driip Production Deployment Script ──────────────────────────────────────
# Deploys to AWS Lambda + Neon Postgres with safe database migrations
#
# Usage:
#   ./deploy.sh                    # Deploy to production
#   ./deploy.sh --dry-run          # Validate without deploying
#   ./deploy.sh --migrate-only     # Run DB migrations only
#   ./deploy.sh --rollback         # Rollback to previous Lambda version
#
# Requirements:
#   - AWS CLI configured
#   - cargo-lambda installed
#   - sqlx-cli installed
#   - .env.prod file with DATABASE_URL

set -euo pipefail

# ── Configuration ────────────────────────────────────────────────────────────
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

AWS_REGION="${AWS_REGION:-ap-southeast-1}"
FUNCTION_NAME="${FUNCTION_NAME:-driip-api}"
ENV_FILE="${ENV_FILE:-.env.prod}"
BACKUP_BUCKET="${BACKUP_BUCKET:-driip-db-backups}"
MIGRATION_TIMEOUT="${MIGRATION_TIMEOUT:-300}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# ── Logging ──────────────────────────────────────────────────────────────────
log_info() { echo -e "${BLUE}ℹ${NC}  $1"; }
log_success() { echo -e "${GREEN}✓${NC} $1"; }
log_warn() { echo -e "${YELLOW}⚠${NC} $1"; }
log_error() { echo -e "${RED}✗${NC} $1"; }

# ── Load Environment ─────────────────────────────────────────────────────────
load_env() {
    if [[ ! -f "$ENV_FILE" ]]; then
        log_error "Environment file not found: $ENV_FILE"
        log_info "Create it with: cp .env.example $ENV_FILE"
        exit 1
    fi
    
    # Export vars from .env.prod
    set -a
    source "$ENV_FILE"
    set +a
    
    # Validate required vars
    [[ -z "${DATABASE_URL:-}" ]] && { log_error "DATABASE_URL not set in $ENV_FILE"; exit 1; }
    [[ -z "${JWT_SECRET:-}" ]] && { log_error "JWT_SECRET not set in $ENV_FILE"; exit 1; }
    
    log_success "Environment loaded from $ENV_FILE"
}

# ── Pre-deployment Checks ─────────────────────────────────────────────────────
pre_checks() {
    log_info "Running pre-deployment checks..."
    
    # Check tools
    command -v aws >/dev/null 2>&1 || { log_error "AWS CLI not found"; exit 1; }
    command -v cargo-lambda >/dev/null 2>&1 || { log_error "cargo-lambda not found. Run: cargo install cargo-lambda"; exit 1; }
    command -v sqlx >/dev/null 2>&1 || { log_error "sqlx-cli not found. Run: cargo install sqlx-cli"; exit 1; }
    
    # Check AWS credentials
    aws sts get-caller-identity >/dev/null 2>&1 || { log_error "AWS credentials not configured"; exit 1; }
    
    # Check Lambda function exists
    aws lambda get-function --function-name "$FUNCTION_NAME" --region "$AWS_REGION" >/dev/null 2>&1 || {
        log_error "Lambda function '$FUNCTION_NAME' not found in region $AWS_REGION"
        log_info "Create it first with: aws lambda create-function ..."
        exit 1
    }
    
    # Check database connectivity
    log_info "Checking database connectivity..."
    if ! timeout 10 psql "$DATABASE_URL" -c "SELECT 1" >/dev/null 2>&1; then
        log_error "Cannot connect to database"
        exit 1
    fi
    log_success "Database connection OK"
    
    # Verify sqlx offline cache exists
    if [[ ! -d ".sqlx" ]]; then
        log_warn ".sqlx/ offline cache not found. Building with live DB..."
        SQLX_OFFLINE=false cargo sqlx prepare 2>/dev/null || true
    fi
}

# ── Database Backup ───────────────────────────────────────────────────────────
backup_database() {
    log_info "Creating database backup..."
    
    local backup_name="driip_$(date +%Y%m%d_%H%M%S).sql.gz"
    
    # Extract host from DATABASE_URL for the backup filename
    local db_host=$(echo "$DATABASE_URL" | sed -n 's/.*@\([^\/]*\).*/\1/p')
    
    # Create backup
    if pg_dump "$DATABASE_URL" --no-owner --no-acl 2>/dev/null | gzip > "/tmp/$backup_name"; then
        log_success "Backup created: /tmp/$backup_name ($(du -h /tmp/$backup_name | cut -f1))"
        
        # Optionally upload to S3 if bucket exists
        if aws s3 ls "s3://$BACKUP_BUCKET" >/dev/null 2>&1; then
            aws s3 cp "/tmp/$backup_name" "s3://$BACKUP_BUCKET/$backup_name"
            log_success "Backup uploaded to S3: s3://$BACKUP_BUCKET/$backup_name"
        fi
        
        echo "$backup_name" > /tmp/last_backup.txt
    else
        log_warn "Backup failed, but continuing with deployment..."
    fi
}

# ── Check Pending Migrations ────────────────────────────────────────────────
check_migrations() {
    log_info "Checking for pending migrations..."
    
    # Get list of pending migrations
    local pending=$(sqlx migrate info --source ./migrations 2>/dev/null | grep "pending" | wc -l)
    
    if [[ "$pending" -eq 0 ]]; then
        log_success "Database is up to date (no pending migrations)"
        return 0
    fi
    
    log_warn "$pending pending migration(s) found"
    sqlx migrate info --source ./migrations 2>/dev/null | grep "pending" || true
    
    return 1
}

# ── Run Migrations (Blue-Green Safe) ────────────────────────────────────────
# Strategy: Run migrations BEFORE deploying new code
# This ensures new code sees the updated schema
# Migrations must be backward compatible (see docs)
run_migrations() {
    log_info "Running database migrations..."
    log_warn "Ensure migrations are backward compatible!"
    
    # Run migrations with timeout
    if timeout "$MIGRATION_TIMEOUT" sqlx migrate run --source ./migrations; then
        log_success "Migrations completed successfully"
        
        # Verify by running cargo sqlx prepare
        log_info "Verifying sqlx offline cache..."
        SQLX_OFFLINE=false cargo sqlx prepare 2>/dev/null || {
            log_warn "sqlx prepare failed, but continuing..."
        }
    else
        log_error "Migration failed! Check database state manually."
        log_info "Rollback with: sqlx migrate revert --source ./migrations"
        exit 1
    fi
}

# ── Build Lambda Binary ─────────────────────────────────────────────────────
build() {
    log_info "Building Lambda binary..."
    
    # Clean previous builds
    rm -rf ./target/lambda
    
    # Build for ARM64 (Graviton2 - cheaper, faster)
    export SQLX_OFFLINE=true
    cargo lambda build --release --target aarch64-unknown-linux-musl
    
    if [[ ! -f ./target/lambda/driip-rust/bootstrap ]]; then
        log_error "Build failed - bootstrap binary not found"
        exit 1
    fi
    
    local binary_size=$(du -h ./target/lambda/driip-rust/bootstrap | cut -f1)
    log_success "Build complete: $binary_size"
}

# ── Deploy to Lambda ────────────────────────────────────────────────────────
deploy_lambda() {
    log_info "Deploying to AWS Lambda..."
    
    # Get current version for potential rollback
    local prev_version=$(aws lambda get-function --function-name "$FUNCTION_NAME" \
        --query 'Configuration.RevisionId' --output text --region "$AWS_REGION")
    echo "$prev_version" > /tmp/prev_lambda_version.txt
    
    # Update function code
    aws lambda update-function-code \
        --function-name "$FUNCTION_NAME" \
        --zip-file fileb://./target/lambda/driip-rust/bootstrap.zip \
        --region "$AWS_REGION" \
        --output json > /tmp/deploy_result.json
    
    log_success "Code uploaded successfully"
    
    # Update environment variables
    log_info "Updating environment variables..."
    aws lambda update-function-configuration \
        --function-name "$FUNCTION_NAME" \
        --environment "Variables={
            DATABASE_URL=$DATABASE_URL,
            JWT_SECRET=$JWT_SECRET,
            JWT_ACCESS_TTL_SECS=${JWT_ACCESS_TTL_SECS:-900},
            JWT_REFRESH_TTL_SECS=${JWT_REFRESH_TTL_SECS:-604800},
            RUST_LOG=${RUST_LOG:-info},
            STRIPE_SECRET_KEY=${STRIPE_SECRET_KEY:-},
            STRIPE_PUBLISHABLE_KEY=${STRIPE_PUBLISHABLE_KEY:-},
            STRIPE_WEBHOOK_SECRET=${STRIPE_WEBHOOK_SECRET:-},
            GHTK_TOKEN=${GHTK_TOKEN:-},
            GHTK_WEBHOOK_SECRET=${GHTK_WEBHOOK_SECRET:-}
        }" \
        --region "$AWS_REGION" \
        --output json > /dev/null
    
    log_success "Environment updated"
    
    # Wait for function to be active
    log_info "Waiting for deployment to complete..."
    aws lambda wait function-updated --function-name "$FUNCTION_NAME" --region "$AWS_REGION"
    
    # Get new version info
    local new_version=$(aws lambda get-function --function-name "$FUNCTION_NAME" \
        --query 'Configuration.RevisionId' --output text --region "$AWS_REGION")
    local last_modified=$(aws lambda get-function --function-name "$FUNCTION_NAME" \
        --query 'Configuration.LastModified' --output text --region "$AWS_REGION")
    
    log_success "Deployed! Revision: $new_version"
    log_info "Last modified: $last_modified"
}

# ── Health Check ────────────────────────────────────────────────────────────
health_check() {
    log_info "Running health check..."
    
    # Get function URL (if using Lambda Function URL)
    local function_url=$(aws lambda get-function-url-config \
        --function-name "$FUNCTION_NAME" \
        --query 'FunctionUrl' --output text --region "$AWS_REGION" 2>/dev/null || echo "")
    
    if [[ -n "$function_url" ]]; then
        # Wait a moment for propagation
        sleep 5
        
        # Test health endpoint
        local status=$(curl -s -o /dev/null -w "%{http_code}" "$function_url/health" 2>/dev/null || echo "000")
        
        if [[ "$status" == "200" ]]; then
            log_success "Health check passed (HTTP 200)"
        else
            log_warn "Health check returned HTTP $status"
            log_info "You may want to verify manually: curl $function_url/health"
        fi
    else
        log_info "No function URL configured. Skipping health check."
        log_info "Configure with: aws lambda create-function-url-config --function-name $FUNCTION_NAME"
    fi
}

# ── Rollback ─────────────────────────────────────────────────────────────────
rollback() {
    log_warn "Rolling back to previous version..."
    
    if [[ ! -f /tmp/prev_lambda_version.txt ]]; then
        log_error "No previous version found. Cannot rollback."
        exit 1
    fi
    
    local prev_version=$(cat /tmp/prev_lambda_version.txt)
    
    # Lambda doesn't support direct revision rollback, but we can restore code from S3
    # For now, just report what we'd need to do
    log_info "Previous version was: $prev_version"
    log_info "To rollback manually:"
    log_info "  1. Find the previous zip in your deployment bucket"
    log_info "  2. Run: aws lambda update-function-code --function-name $FUNCTION_NAME --zip-file fileb://<previous.zip>"
    log_info "  3. Or revert database migrations if needed"
}

# ── Dry Run ───────────────────────────────────────────────────────────────────
dry_run() {
    log_info "=== DRY RUN MODE ==="
    log_info "Would perform the following actions:"
    echo ""
    echo "  1. Load environment from: $ENV_FILE"
    echo "  2. Run pre-deployment checks"
    echo "  3. Backup database"
    echo "  4. Check for pending migrations"
    echo "  5. Run migrations (if any)"
    echo "  6. Build Lambda binary (SQLX_OFFLINE=true)"
    echo "  7. Deploy to Lambda: $FUNCTION_NAME ($AWS_REGION)"
    echo "  8. Update environment variables"
    echo "  9. Run health check"
    echo ""
    
    # Show current status
    log_info "Current status:"
    
    if [[ -f "$ENV_FILE" ]]; then
        log_success "  ✓ $ENV_FILE exists"
    else
        log_error "  ✗ $ENV_FILE missing"
    fi
    
    if aws lambda get-function --function-name "$FUNCTION_NAME" --region "$AWS_REGION" >/dev/null 2>&1; then
        log_success "  ✓ Lambda function exists"
    else
        log_error "  ✗ Lambda function not found"
    fi
    
    if [[ -d ".sqlx" ]]; then
        local query_count=$(find .sqlx -name "*.json" | wc -l)
        log_success "  ✓ Offline cache ready ($query_count queries)"
    else
        log_warn "  ⚠ No offline cache (.sqlx/ missing)"
    fi
    
    log_info "=== END DRY RUN ==="
}

# ── Main ─────────────────────────────────────────────────────────────────────
main() {
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "  🚀 Driip Production Deployment"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    
    case "${1:-}" in
        --dry-run)
            dry_run
            exit 0
            ;;
        --migrate-only)
            load_env
            backup_database
            run_migrations
            exit 0
            ;;
        --rollback)
            rollback
            exit 0
            ;;
        --help|-h)
            cat << 'EOF'
Usage: ./deploy.sh [OPTION]

Options:
  (none)          Full deployment with migrations
  --dry-run       Show what would be done without executing
  --migrate-only  Run database migrations only
  --rollback      Show rollback instructions
  --help          Show this help

Environment:
  ENV_FILE        Path to env file (default: .env.prod)
  AWS_REGION      AWS region (default: ap-southeast-1)
  FUNCTION_NAME   Lambda function name (default: driip-api)

Examples:
  ./deploy.sh --dry-run                    # Preview deployment
  ENV_FILE=.env.staging ./deploy.sh        # Deploy to staging
EOF
            exit 0
            ;;
    esac
    
    # Full deployment
    load_env
    pre_checks
    backup_database
    
    # Phase 1: Database (before code)
    if ! check_migrations; then
        run_migrations
    fi
    
    # Phase 2: Build & Deploy
    build
    deploy_lambda
    health_check
    
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    log_success "Deployment complete!"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
}

main "$@"
