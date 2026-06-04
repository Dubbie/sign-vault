# Contributing to SignVault

Thanks for your interest in contributing. This document covers how to get the full stack running locally and the conventions used across the project.

---

## Monorepo layout

```
sign-vault/
├── api/         # Laravel 13 REST API
├── frontend/    # Vue 3 main app  → https://localhost:5173
└── landing/     # Vue 3 marketing/stats page  → https://localhost:5174
```

---

## Prerequisites

| Tool | Version |
|---|---|
| [DDEV](https://ddev.com/get-started/) | latest |
| Node.js | `^20.19.0` or `>=22.12.0` |
| npm | bundled with Node |

DDEV manages PHP, Composer, and the database for the API — no manual PHP install needed.

---

## Local setup

### 1. API

```bash
cd api
ddev start
ddev composer install
ddev artisan migrate
```

The API will be available at `https://api.sign-vault.ddev.site`.

Local object storage runs on MinIO. If signed URLs return `AccessDenied`, make the bucket publicly readable:

```bash
docker run --rm --network host --entrypoint sh minio/mc:latest -lc '
mc alias set local http://localhost:9000 minio minio_password &&
mc anonymous set download local/sign-vault-local
'
```

### 2. Frontend

```bash
cd frontend
npm install
cp .env.example .env
npm run dev
```

Opens at `https://localhost:5173`. Accept the self-signed certificate warning in your browser.

### 3. Landing page

```bash
cd landing
npm install
cp .env.example .env
npm run dev
```

Opens at `https://localhost:5174`.

---

## Running tests & linting

```bash
# API
ddev artisan test

# Frontend / Landing
npm run test:unit   # Vitest unit tests
npm run lint        # Oxlint + ESLint
npm run format      # Prettier
```

---

## Discord OAuth in development

You'll need a Discord application with a redirect URI pointing at your local API callback. Create one at [discord.com/developers/applications](https://discord.com/developers/applications) and add the credentials to `api/.env`:

```env
DISCORD_CLIENT_ID=your_client_id
DISCORD_CLIENT_SECRET=your_client_secret
DISCORD_REDIRECT_URI=https://api.sign-vault.ddev.site/api/auth/discord/callback
```

---

## Submitting changes

1. Fork the repo and create a branch from `main`.
2. Make your changes. Add or update tests where relevant.
3. Run the test and lint commands above and make sure everything passes.
4. Open a pull request against `main` with a clear description of what changed and why.

For larger changes, open an issue first so we can discuss the approach before you invest the time.
