# SignVault API

> Trackmania sign hosting and sharing platform — backend API

SignVault is a platform for Trackmania players to upload, organise, and share in-game signs with the community. This is the Laravel API backend, part of the [sign-vault monorepo](https://github.com/Dubbie/sign-vault).

## Features

- **Multi-provider OAuth2 authentication** — Discord and Trackmania/Ubisoft login with CSRF-protected state flow and account linking
- **Folder management** — Create, edit, delete, and organise sign folders, including alternate variants of a folder's sign set
- **Sign uploads & hosting** — Upload Trackmania sign images and WebM/AVIF animations to S3-compatible storage with stable URLs, in batches grouped under a shared upload session for cleaner activity-log entries
- **WebP thumbnails** — Auto-generated, height-capped WebP previews for signs (plus a backfill command for pre-existing uploads) so preview grids load fast without serving full-resolution originals
- **Public sharing & voting** — Share folders publicly or protect them with a password, with community `++` voting and sortable browsing
- **Admin moderation** — User management, content moderation, and a persistent activity log
- **Engagement analytics** — Anonymized tracking of public folder views/previews and sign copies, deduplicated per visitor via a one-way HMAC-SHA256 hash of their IP address, kept separate from the raw IPs recorded in admin activity logs

## Tech Stack

| Layer          | Technology                                                                                            |
| -------------- | ----------------------------------------------------------------------------------------------------- |
| Framework      | [Laravel 13](https://laravel.com/)                                                                    |
| Language       | [PHP 8.4](https://www.php.net/)                                                                       |
| Auth           | [Laravel Sanctum](https://laravel.com/docs/sanctum) + [Socialite](https://laravel.com/docs/socialite) |
| Database       | MySQL or PostgreSQL                                                                                   |
| Object Storage | Cloudflare R2 (production) / MinIO (local)                                                            |
| Local Dev      | [DDEV](https://ddev.com/)                                                                             |

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

| Command                | Description                                  |
| ---------------------- | -------------------------------------------- |
| `ddev artisan test`    | Run the test suite                           |
| `ddev artisan migrate` | Run database migrations                      |
| `ddev artisan tinker`  | Open an interactive shell                    |
| `ddev npm run dev`     | Start the Vite dev server for asset building |
