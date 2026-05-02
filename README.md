# Driip Monorepo

This repository is organized into three separate apps:

- `home/` — public storefront built with Nuxt
- `panel/` — internal staff/admin app
- `backend/` — Laravel 12 backend for orders, tracking, permissions, and staff workflows
- `driip-rust/` - Beta backend , serverless, AWS.

## Structure

- `home/` contains the current storefront code.
- `panel/` is reserved for staff operations and administration.
- `backend/` will hold the Laravel 12 system of record.
- `driip-rust/` This is the backend, but coded with Rust, faster, but unfinished.

## Local development

- Run the storefront from `home/`.
- Run the admin panel from `panel/`.
- Run the backend from `backend/`.
- Run the driip-rust from `driip-rust/`.

The repository root acts as a workspace shell and shared documentation layer.
