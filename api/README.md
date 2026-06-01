# SignVault API

Backend API for SignVault, a Trackmania sign hosting and sharing platform.

## Overview

SignVault allows users to:

- Authenticate using Discord
- Upload Trackmania sign images
- Organize signs into folders
- Share folders publicly
- Protect folders with passwords
- Copy stable image URLs for use directly inside Trackmania

The frontend is a separate Vue application.

## Tech Stack

- Laravel 13
- PHP 8.4+
- Laravel Sanctum
- Laravel Socialite
- MySQL or PostgreSQL
- Cloudflare R2 (production)
- MinIO (local development)
- DDEV

## Local Development

Start DDEV:

```bash
ddev start
```

Install dependencies:

```bash
ddev composer install
```

Run migrations:

```bash
ddev artisan migrate
```

Run tests:

```bash
ddev artisan test
```

## Object Storage

Local development uses MinIO.

MinIO Console:

```text
http://localhost:9001
```

Default bucket:

```text
sign-vault-local
```

If direct `public_url` links return `AccessDenied`, make the bucket publicly readable:

```bash
docker run --rm --network host --entrypoint sh minio/mc:latest -lc '
mc alias set local http://localhost:9000 minio minio_password &&
mc anonymous set download local/sign-vault-local
'
```

Production uses Cloudflare R2 through Laravel's S3 filesystem driver.

## Authentication

Authentication is handled through Discord OAuth.

Traditional email/password registration is not part of the MVP.

The Discord flow uses a one-time `state` value for CSRF protection. The
`/api/auth/discord/redirect` endpoint returns the authorization URL and the
generated `state`; the frontend must forward both the `code` and `state` values
to `/api/auth/discord/callback`.

## Project Status

Current MVP goals:

- Discord authentication
- Folder management
- Sign uploads
- Public folder sharing
- Password-protected folders

Future features may include:

- Teams
- Shared ownership
- Moderation tools
- Search
- Collections

## Documentation

See AGENTS.md for AI agent instructions and project conventions.
