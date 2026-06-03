# SignVault API

> Trackmania sign hosting and sharing platform — backend API

[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](../frontend/LICENSE)

SignVault is a platform for Trackmania players to upload, organise, and share in-game signs with the community. This repository contains the Laravel API backend.

**Frontend repository:** [Dubbie/sign-vault-frontend](https://github.com/Dubbie/sign-vault-frontend)

## Features

- **Discord OAuth2 authentication** — One-click login with CSRF-protected state flow
- **Folder management** — Create, edit, delete, and organise sign folders
- **Sign uploads & hosting** — Upload Trackmania sign images with stable URLs
- **Public sharing** — Share folders publicly or protect them with a password
- **Admin moderation** — User management and content moderation tools

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | [Laravel 13](https://laravel.com/) |
| Language | [PHP 8.4](https://www.php.net/) |
| Auth | [Laravel Sanctum](https://laravel.com/docs/sanctum) + [Socialite](https://laravel.com/docs/socialite) |
| Database | MySQL or PostgreSQL |
| Object Storage | Cloudflare R2 (production) / MinIO (local) |
| Local Dev | [DDEV](https://ddev.com/) |

## Requirements

- [DDEV](https://ddev.com/get-started/)
- PHP 8.4+ (managed by DDEV)
- Composer (managed by DDEV)

## Getting Started

### 1. Start the environment

```bash
ddev start
```

### 2. Install dependencies

```bash
ddev composer install
```

### 3. Run migrations

```bash
ddev artisan migrate
```

### 4. Run tests

```bash
ddev artisan test
```

## Object Storage

Local development uses **MinIO**. The MinIO console is available at:

```text
http://localhost:9001
```

Default bucket: `sign-vault-local`

If direct `public_url` links return `AccessDenied`, make the bucket publicly readable:

```bash
docker run --rm --network host --entrypoint sh minio/mc:latest -lc '
mc alias set local http://localhost:9000 minio minio_password &&
mc anonymous set download local/sign-vault-local
'
```

Production uses **Cloudflare R2** through Laravel's S3 filesystem driver. Configure it via your `.env`:

```env
FILESYSTEM_DISK=r2
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_BUCKET=your_bucket
AWS_ENDPOINT=https://your_account.r2.cloudflarestorage.com
AWS_URL=https://pub-xxxxx.r2.dev
AWS_USE_PATH_STYLE_ENDPOINT=false
```

## Authentication

SignVault uses **Discord OAuth2** with a one-time `state` value for CSRF protection. The flow:

1. `GET /api/auth/discord/redirect` — Returns the Discord authorization URL and a generated `state`
2. The frontend redirects the user to Discord
3. `POST /api/auth/discord/callback` — Exchanges the `code` and `state` for a bearer token

Traditional email/password registration is not currently supported.

## Scripts

| Command | Description |
|---|---|
| `ddev artisan test` | Run the test suite |
| `ddev artisan migrate` | Run database migrations |
| `ddev artisan tinker` | Open an interactive shell |
| `ddev npm run dev` | Start the Vite dev server for asset building |

## License

[MIT](../frontend/LICENSE)
