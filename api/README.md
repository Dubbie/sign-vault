# SignVault API

> Trackmania sign hosting and sharing platform — backend API

SignVault is a platform for Trackmania players to upload, organise, and share in-game signs with the community. This is the Laravel API backend, part of the [sign-vault monorepo](https://github.com/Dubbie/sign-vault).

## Features

- **Multi-provider OAuth2 authentication** — Discord and Trackmania/Ubisoft login with CSRF-protected state flow and account linking
- **Folder management** — Create, edit, delete, and organise sign folders, including alternate variants of a folder's sign set
- **Sign uploads & hosting** — Upload Trackmania sign images and WebM/AVIF animations directly to S3-compatible storage with presigned browser uploads, then finalize them through the API for cleaner activity-log entries
- **WebP thumbnails** — Auto-generated, height-capped WebP previews for signs, generated asynchronously after upload so the request path stays fast
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

Expected local environment:

```env
FILESYSTEM_DISK=s3

AWS_ACCESS_KEY_ID=minio
AWS_SECRET_ACCESS_KEY=minio_password
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=sign-vault-local
AWS_ENDPOINT=http://localhost:9000
AWS_INTERNAL_ENDPOINT=http://host.docker.internal:9000
AWS_URL=http://localhost:9000/sign-vault-local
AWS_USE_PATH_STYLE_ENDPOINT=true
```

If direct `public_url` links return `AccessDenied`, make the bucket publicly readable:

```bash
docker run --rm --network host --entrypoint sh minio/mc:latest -lc '
mc alias set local http://localhost:9000 minio minio_password &&
mc anonymous set download local/sign-vault-local
'
```

This local MinIO server already answers browser preflight requests for direct `PUT` uploads from `https://localhost:5173`, but the bucket CORS API is not writable on this setup. If uploads stop working locally, verify the browser-facing URL is still `http://localhost:9000` and the backend-only endpoint remains `http://host.docker.internal:9000`.

Production uses **Cloudflare R2** through Laravel's S3 filesystem driver. Configure it via your `.env`:

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_DEFAULT_REGION=auto
AWS_BUCKET=your_bucket
AWS_ENDPOINT=https://your_account.r2.cloudflarestorage.com
AWS_URL=https://pub-xxxxx.r2.dev
AWS_USE_PATH_STYLE_ENDPOINT=false
```

### R2 CORS

Browser uploads use presigned `PUT` URLs, so the bucket must allow your frontend origin through CORS. In the Cloudflare dashboard:

1. Open `R2` -> your bucket -> `Settings`
2. Under `CORS Policy`, add a rule for your frontend origin

Example policy:

```json
[
    {
        "AllowedOrigins": ["https://your-frontend-domain.com"],
        "AllowedMethods": ["PUT", "GET", "HEAD"],
        "AllowedHeaders": ["Content-Type"],
        "ExposeHeaders": ["ETag"],
        "MaxAgeSeconds": 3600
    }
]
```

If you manage R2 with Wrangler:

```bash
npx wrangler r2 bucket cors set <BUCKET_NAME> --file cors.json
npx wrangler r2 bucket cors list <BUCKET_NAME>
```

### Queue Workers

Thumbnail generation is now asynchronous. Production must run a queue worker or uploaded images will remain in `thumbnail_status = pending`.

At minimum, run a worker that listens to `sign-thumbnails`:

```bash
php artisan queue:work --queue=sign-thumbnails,default
```

Use a real async queue backend in production. `sync` is not suitable for this feature.

### Upload Flow

The frontend now uploads signs in three steps:

1. `POST /api/folders/{folder}/signs/uploads/prepare` to request presigned upload URLs
2. Direct browser upload to object storage using the returned `upload_url`
3. `POST /api/folders/{folder}/signs/uploads/complete` to verify storage objects, create sign rows, and dispatch thumbnail jobs

New image uploads may return `thumbnail_url: null` with `thumbnail_status: pending` until the queue job finishes.

### Deploy Checklist

Before deploying this upload flow:

1. Run the latest migrations
2. Set production S3/R2 environment variables
3. Configure bucket CORS for the frontend origin
4. Ensure queue workers are running for `sign-thumbnails`
5. Clear and rebuild Laravel config cache during deploy
6. Deploy backend and frontend together so the new endpoints exist before the new client code is live

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
