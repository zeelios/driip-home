# driip-rust API Integration Tests

End-to-end API testing suite using Bun's built-in test runner.

## Prerequisites

- [Bun](https://bun.sh) installed
- driip-rust backend running (default: `http://localhost:8000`)
- Admin user seeded (run `cargo run --bin seed_admin` first)

## Quick Start

```bash
cd /Users/zeelios/Documents/Repos/driip/driip-rust/tests

# Install types (optional, for IDE support)
bun install

# Run all tests
bun test

# Or with explicit API URL
API_URL=http://localhost:8000 bun test
```

## Test Coverage

| Suite               | Tests                                                         |
| ------------------- | ------------------------------------------------------------- |
| **Authentication**  | Login (success/fail), token refresh, protected routes, logout |
| **Dashboard/Stats** | `GET /orders/stats`                                           |
| **Products**        | CRUD: list, create, get, update, delete                       |
| **Orders**          | List, queue                                                   |
| **Purchase Orders** | Create, get detail, cancel                                    |
| **Staff**           | List all, get current profile                                 |
| **Inventory**       | Low stock items                                               |

## Environment Variables

| Variable  | Default                 | Description                |
| --------- | ----------------------- | -------------------------- |
| `API_URL` | `http://localhost:8000` | Base URL of driip-rust API |

## Test Flow

```
1. Login with admin@driip.io / password
2. Store access_token + refresh_token
3. Run permissioned endpoint tests
4. Cleanup: Logout (invalidate refresh token)
```

## Troubleshooting

| Issue              | Fix                                           |
| ------------------ | --------------------------------------------- |
| `ECONNREFUSED`     | Start the backend: `cargo run`                |
| `401 Unauthorized` | Run seed_admin first to create test user      |
| `403 Forbidden`    | Check admin user has proper permissions in DB |

## Manual Testing with curl

```bash
# Login (note: /api/v1 prefix)
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@driip.io","password":"password"}'

# Use token
curl http://localhost:8000/api/v1/orders/stats \
  -H "Authorization: Bearer <access_token>"

# Health check (no prefix, no auth required)
curl http://localhost:8000/health
```
