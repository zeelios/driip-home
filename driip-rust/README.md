# driip-rust — Backend API

Rust + Axum backend for the Driip platform. Runs as a **regular HTTP server locally** and as an **AWS Lambda function in production** — the same binary, zero code change between environments.

---

## Table of Contents

1. [Local Development](#1-local-development)
2. [What is AWS Lambda / Serverless?](#2-what-is-aws-lambda--serverless)
3. [How This Project Works on Lambda](#3-how-this-project-works-on-lambda)
4. [Why Use This Architecture?](#4-why-use-this-architecture)
5. [What You Need to Do to Deploy](#5-what-you-need-to-do-to-deploy)
6. [Infrastructure Overview](#6-infrastructure-overview)
7. [Domain Architecture](#7-domain-architecture)
8. [Environment Variables Reference](#8-environment-variables-reference)
9. [Testing the API](#9-testing-the-api)
10. [Common Questions](#10-common-questions)

---

## 1. Local Development

**Requirements:** Rust, Docker, Bun (or just Docker)

```bash
# 1. Start Postgres + Redis
docker compose up -d db redis

# 2. Run migrations
DATABASE_URL=postgres://driip:driip_dev@localhost:5432/driip \
  sqlx migrate run

# 3. Seed first admin user
SQLX_OFFLINE=true DATABASE_URL=postgres://driip:driip_dev@localhost:5432/driip \
  cargo run -- seed-admin admin@driip.vn yourpassword

# 4. Start the server (hot-reload)
SQLX_OFFLINE=true cargo run
# → listening on http://localhost:8000
```

Or use the Makefile shortcuts:

```bash
make db-up       # start Docker services
make migrate     # run migrations
make dev         # cargo run with SQLX_OFFLINE=true
make test-api    # run the full API test suite
```

---

## 2. What is AWS Lambda / Serverless?

If you have been running VPS/servers your whole life, here is the mental model shift:

### The old way (VPS / traditional server)

```
You rent a server (EC2, VPS, etc.)
→ You pay 24/7 whether anyone uses it or not
→ You manage OS, updates, restarts, crash recovery
→ You worry about "will it handle a traffic spike?"
→ You SSH in to debug
```

### The serverless way (Lambda)

```
You upload your code/binary
→ AWS runs it only when a request comes in
→ You pay per request (first 1 million requests/month are FREE)
→ AWS handles OS, scaling, crash recovery, availability zones
→ Zero requests = $0.00 cost
```

### The key insight

Lambda is not a different kind of app. It is just **a different way to host the same app**. Your Rust binary is exactly the same. The only difference is who manages the server and how it starts up.

This project detects which environment it is in at runtime:

```rust
// src/main.rs
if std::env::var("AWS_LAMBDA_FUNCTION_NAME").is_ok() {
    // Running on Lambda — AWS injects this env var
    lambda_http::run(app).await...
} else {
    // Running locally — bind a TCP port like a normal server
    axum::serve(listener, app).await...
}
```

You write the API with Axum exactly as normal. The Lambda wrapper just translates the AWS event format into a regular HTTP request and hands it to Axum.

---

## 3. How This Project Works on Lambda

```
Internet
   │
   ▼
API Gateway (AWS)          ← acts as the "nginx" / reverse proxy
   │  receives HTTP requests, forwards them as events
   ▼
Lambda Function            ← your Rust binary runs here
   │  lambda_http crate translates the event → Axum request
   ▼
Axum Router                ← same router you use locally
   │
   ├── RDS / Neon (Postgres)   ← database (serverless-friendly)
   └── Upstash (Redis)          ← cache / rate limiting (serverless Redis)
```

### Cold start

When no requests have come in for a while (~5–15 min), the Lambda container is shut down. The next request triggers a **cold start** — the binary boots from scratch. For Rust this is extremely fast (~50–200ms) compared to JVM or Node.js apps (~1–3s). After the first request, subsequent requests reuse the warm container.

### Stateless requirement

Lambda functions must be **stateless** — no global in-memory state that persists between requests (the container may be reused or replaced at any time). This project already follows this:

- All state lives in Postgres or Redis
- JWT is validated from the token itself, not a server-side session
- The `AppState` struct holds only connection pools (which survive across warm invocations)

---

## 4. Why Use This Architecture?

Here is an honest comparison for a project like Driip:

| Concern                    | VPS (e.g. DigitalOcean $6/mo)      | Lambda                               |
| -------------------------- | ---------------------------------- | ------------------------------------ |
| **Cost at low traffic**    | $6/mo fixed                        | ~$0/mo (free tier covers it)         |
| **Cost at high traffic**   | Same $6/mo (or upgrade)            | Scales with usage, can get expensive |
| **Setup time**             | Install nginx, systemd, certbot... | Deploy one command                   |
| **Scaling**                | Manual (resize droplet)            | Automatic, instant                   |
| **Availability**           | Single region, single VM           | Multi-AZ by default                  |
| **SSH / debugging**        | Direct shell access                | CloudWatch logs, no SSH              |
| **Persistent connections** | Normal                             | Tricky — use RDS Proxy               |
| **WebSockets**             | Easy                               | Not supported natively               |
| **Cron jobs**              | crontab / systemd timer            | EventBridge Scheduler                |
| **Best for**               | Long-running, stateful, cheap      | Bursty, stateless, scale-to-zero     |

**For Driip specifically:** Lambda makes sense because:

- Traffic is unpredictable (product drops, campaigns)
- Cost is near-zero when no orders are coming in
- No ops overhead — no server to maintain
- The Rust binary is fast enough that cold starts are not a real problem

---

## 5. What You Need to Do to Deploy

### One-time setup (do this once)

#### Step 1 — Install tools

```bash
# Install Cargo Lambda (the deploy tool)
cargo install cargo-lambda

# Install AWS CLI
brew install awscli

# Configure AWS credentials (you need an AWS account)
aws configure
# → Enter: Access Key ID, Secret Access Key, region (e.g. ap-southeast-1), output: json
```

You get AWS credentials from: **AWS Console → IAM → Users → your user → Security credentials → Access keys**

#### Step 2 — Create the database

Use [Neon](https://neon.tech) (free serverless Postgres) or AWS RDS. Get the connection string:

```
postgres://user:pass@host/dbname?sslmode=require
```

#### Step 3 — Run migrations against the production DB

```bash
DATABASE_URL="postgres://..." sqlx migrate run
```

#### Step 4 — Deploy

```bash
# Build for Lambda (cross-compiles to Amazon Linux 2 ARM)
cargo lambda build --release --arm64

# Deploy (first time: creates the Lambda + IAM role automatically)
cargo lambda deploy driip-rust \
  --env-var DATABASE_URL="postgres://..." \
  --env-var JWT_SECRET="..." \
  --env-var GHTK_TOKEN="..." \
  --env-var GHTK_SANDBOX="false" \
  --env-var GHTK_WEBHOOK_SECRET="..."
```

#### Step 5 — Set up API Gateway

In AWS Console → API Gateway → Create HTTP API → integrate with your Lambda function → deploy.

Or with AWS CLI:

```bash
# This creates the public URL that hits your Lambda
aws apigatewayv2 create-api \
  --name driip-api \
  --protocol-type HTTP \
  --target arn:aws:lambda:ap-southeast-1:ACCOUNT_ID:function:driip-rust
```

### Every subsequent deploy (just one command)

```bash
cargo lambda build --release --arm64 && cargo lambda deploy driip-rust
```

---

## 6. Infrastructure Overview

```
┌─────────────────────────────────────────────────┐
│                   AWS (ap-southeast-1)           │
│                                                  │
│  Route53 (DNS)                                   │
│      │                                           │
│  API Gateway (HTTP API)                          │
│      │  $0.001 per 1000 requests                 │
│      │                                           │
│  Lambda Function: driip-rust                     │
│      │  128MB RAM, arm64, Rust binary             │
│      │  $0 for first 1M req/month                │
│      │                                           │
│      ├── Neon / RDS Postgres (database)          │
│      └── Upstash Redis (cache, rate limit)       │
└─────────────────────────────────────────────────┘
```

### Recommended services

| Service                        | What it does        | Why                                               |
| ------------------------------ | ------------------- | ------------------------------------------------- |
| [Neon](https://neon.tech)      | Serverless Postgres | Scales to zero, generous free tier, no VPC needed |
| [Upstash](https://upstash.com) | Serverless Redis    | HTTP-based Redis, works from Lambda without VPC   |
| AWS API Gateway v2             | HTTP routing        | Cheapest, lowest latency, auto-handles HTTPS      |
| AWS CloudWatch                 | Logs & monitoring   | All `tracing::info!()` logs go here automatically |

---

## 7. Domain Architecture

The backend follows **Domain-Driven Design (DDD)** — each business concept lives in its own folder under `src/domain/`:

```
src/domain/
├── address/          # Centralized address book + fraud blocking
├── customer/         # Customer profiles (address moved to address domain)
├── fulfillment/      # GHTK courier integration, fee catalog, shipments
├── identity/         # Staff auth (login, refresh, JWT)
├── inventory/        # Stock tracking across warehouses
├── notification/     # Internal alert system
├── order/            # Orders with shipping_address_id
├── product/          # Product catalog
├── purchase_order/   # Supplier restock orders
├── public/           # Customer-facing API (orders, addresses, auth)
└── warehouse/        # Warehouses linked to address records
```

Each domain contains:

- **`model.rs`** — structs, DTOs, validation rules
- **`repository.rs`** — SQLx queries (the only place that touches the DB directly)
- **`service.rs`** — business logic (e.g. fraud scoring, fee calculation)
- **`handler.rs`** — Axum HTTP handlers
- **`mod.rs`** — exports and route wiring

### Address model (new)

Addresses are now **centralized** — not inlined into customers or warehouses:

- `addresses` table — one record per unique address
- `customer_addresses` junction — many-to-many, with `is_default` flag
- `warehouses.address_id` — one-to-one
- `orders.shipping_address_id` — every order records which address was used

Fraud prevention:

- `strike_count` auto-increments when an order is cancelled
- `status` can be `active`, `flagged`, or `blocked`
- Staff can manually block/unblock addresses

### GHTK pickup address

The fulfillment service now reads the pickup address from the **first active warehouse's linked address record**. The `GHTK_PICK_*` env vars are only used as a fallback.

---

## 8. Environment Variables Reference

```env
# Database
DATABASE_URL=postgres://user:pass@host/db

# Server (ignored on Lambda, Lambda does not use a port)
PORT=8000

# Auth
JWT_SECRET=change-this-in-production-min-32-chars
JWT_ACCESS_TTL_SECS=900        # 15 minutes
JWT_REFRESH_TTL_SECS=2592000   # 30 days

# Cache (Upstash Redis)
UPSTASH_URL=https://xxx.upstash.io
UPSTASH_TOKEN=xxx

# GHTK Courier
GHTK_TOKEN=your-ghtk-api-token
GHTK_SANDBOX=false             # true = test mode, false = live
GHTK_WEBHOOK_SECRET=your-hmac-secret

# GHTK Pickup address — OPTIONAL fallback
# If set, used when no active warehouse has a linked address.
# If omitted, the app reads the address from the first active warehouse.
GHTK_PICK_NAME=Driip Warehouse
GHTK_PICK_ADDRESS=123 Nguyen Van Linh
GHTK_PICK_PROVINCE=TP. Ho Chi Minh
GHTK_PICK_DISTRICT=Quan 7
GHTK_PICK_TEL=0909000000
```

### Health check

```bash
curl http://localhost:8000/health
# → {"status":"ok"}
```

---

## 9. Testing the API

### Run cargo check (fast, no DB needed)

```bash
SQLX_OFFLINE=true cargo check
```

### Run the full test suite (requires server running locally)

```bash
bash test-api.sh
```

The script:

1. Cleans up stale test data from previous runs
2. Seeds the admin user
3. Tests every endpoint with correct auth, payloads, and edge cases
4. Reports pass/fail count

> **Note:** `test-api.sh` is currently being updated for the new address flow. After the address refactor, customers and warehouses no longer accept inline address fields — addresses must be created via the address endpoints first. The script will be updated to match.

### Manual login

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@driip.vn","password":"yourpassword"}'
```

---

## 10. Common Questions

**Q: Do I need to restart the server when I change env vars?**
Locally: yes, restart `cargo run`. On Lambda: run `cargo lambda deploy` with the new `--env-var` flags, or update them in the AWS Console → Lambda → Configuration → Environment variables.

**Q: How do I see logs in production?**
AWS Console → CloudWatch → Log groups → `/aws/lambda/driip-rust`. Every `tracing::info!()` / `tracing::error!()` call in the code shows up here.

**Q: My Lambda keeps timing out on the first request. Why?**
The default Lambda timeout is 3 seconds. For a Rust binary connecting to Postgres, the cold start can take 1–2s. Go to Lambda → Configuration → General configuration → set timeout to **15 seconds**.

**Q: I get "connection pool exhausted" errors.**
Lambda can run many concurrent instances, each with its own pool. Use `max_connections=2` in your `DATABASE_URL` (e.g. `?pool_max_connections=2`) and set up **RDS Proxy** or use **Neon** (which handles connection pooling on their side automatically).

**Q: Can I use Lambda for WebSockets (e.g. live order tracking)?**
Not with HTTP Lambda. Use **API Gateway WebSocket API** with a separate Lambda, or use a hosted WebSocket service (Pusher, Ably, Upstash Qstash) and push from this API.

**Q: How do I roll back a bad deploy?**
Lambda keeps all previous versions. In the console: Lambda → your function → Versions → publish version before deploying, then point the alias back. Or simply redeploy the previous git commit.

**Q: I want to test this on Lambda without affecting production.**
Create two Lambda functions: `driip-rust-staging` and `driip-rust-prod`. Deploy to staging first, test, then deploy to prod. Both use the same binary, different env vars.
