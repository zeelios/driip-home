#!/bin/bash
# API Integration Test Runner for driip-rust
# Usage: ./run-tests.sh [API_URL]

set -e

API_URL="${1:-http://localhost:8000}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "═══════════════════════════════════════════════════════"
echo "  driip-rust API Integration Tests"
echo "═══════════════════════════════════════════════════════"
echo ""
echo "API Endpoint: $API_URL"
echo ""

# Check if bun is installed
if ! command -v bun &> /dev/null; then
    echo "❌ Bun is not installed. Please install Bun first:"
    echo "   https://bun.sh/docs/installation"
    exit 1
fi

# Health check
echo "→ Checking API health..."
if ! curl -sf "$API_URL/health" &> /dev/null; then
    echo "⚠️  API at $API_URL is not responding"
    echo "   Make sure driip-rust is running: cargo run"
    exit 1
fi
echo "   ✓ API is online"
echo ""

# Run tests
echo "→ Running tests..."
echo ""
cd "$SCRIPT_DIR"
API_URL="$API_URL" bun test api_integration.test.ts

echo ""
echo "═══════════════════════════════════════════════════════"
echo "  ✓ Tests complete"
echo "═══════════════════════════════════════════════════════"
