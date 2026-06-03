# SignVault

SignVault is a community platform for the Trackmania game where players can host, organize, and share custom in-game sign images. Users authenticate with Discord, upload signs into folders, and get stable CDN-backed URLs they can paste directly into Trackmania. Folders can be kept private, shared publicly, or protected with a password.

---

## Monorepo Structure

```
sign-vault/
├── api/         # Laravel 13 REST API — authentication, storage, business logic
├── frontend/    # Vue 3 app — the main authenticated user-facing application
└── landing/     # Vue 3 app — public marketing and live stats page
```

All three packages are independently deployable but share the same domain model and communicate over HTTP.

---

## Packages

### `api` — Laravel REST API

The backend for the entire platform. Handles Discord OAuth2, file uploads to object storage, folder and sign management, and admin moderation tooling.

**Key responsibilities:**
- Discord OAuth2 login via Laravel Socialite — no email/password accounts
- Token-based session management via Laravel Sanctum
- Folder management with three visibility tiers: `private`, `public`, and `password`
- Sign uploads (PNG, JPEG, WebP) stored on Cloudflare R2 in production and MinIO locally
- Public sign browsing and password-protected folder unlocking
- Admin endpoints for user banning and content moderation
- Public stats endpoint (users, signs, uptime, CDN latency) consumed by the landing page

**Tech stack:**

| | |
|---|---|
| Framework | Laravel 13 |
| Language | PHP 8.4 |
| Authentication | Discord OAuth2 + Laravel Sanctum tokens |
| Object storage | Cloudflare R2 (prod) · MinIO (dev) |
| Local dev environment | DDEV |

→ See [`api/README.md`](api/README.md) for setup instructions.

---

### `frontend` — Vue 3 Application

The main application, accessible to authenticated users. Provides the full sign management experience: uploading, organizing, previewing, and sharing collections.

**Key features:**
- Browse public sign folder collections with image grid previews
- Create and manage personal folders with configurable visibility
- Upload signs via drag-and-drop or file picker
- Move signs between folders in bulk
- Copy stable CDN URLs for direct use in Trackmania
- View and unlock password-protected public folders
- Admin dashboard for user management (ban/unban) and content moderation
- Route guards enforce authentication; Discord OAuth callback is handled in-app

**Tech stack:**

| | |
|---|---|
| Framework | Vue 3 (Composition API) |
| Language | TypeScript |
| State management | Pinia |
| Routing | Vue Router |
| Styling | Tailwind CSS 4 |
| Build tool | Vite |
| Testing | Vitest |

→ See [`frontend/README.md`](frontend/README.md) for setup instructions.

---

### `landing` — Marketing & Stats Page

A lightweight public-facing site that introduces SignVault to new visitors, shows live platform statistics pulled from the API, and directs users to log in or browse signs.

**Key sections:**
- Hero with headline and call-to-action buttons (login / browse)
- Live animated stats: total users, total signs, uptime, CDN latency
- Feature highlights: sign previews, visibility controls, Discord login
- Links to the app, Discord community, and legal pages

**Tech stack:**

| | |
|---|---|
| Framework | Vue 3 |
| Styling | Tailwind CSS 4 |
| Build tool | Vite |

→ See [`landing/README.md`](landing/README.md) for setup instructions.

---

## Authentication Flow

SignVault uses Discord OAuth2 exclusively — there is no email/password registration.

1. The frontend calls `GET /api/auth/discord/redirect` → receives a Discord authorization URL and a `state` value
2. The user is redirected to Discord and authorizes the application
3. Discord redirects back to the frontend callback route with a `code` and `state`
4. The frontend POSTs both to `POST /api/auth/discord/callback` → receives a bearer token
5. The token is stored client-side and sent as `Authorization: Bearer {token}` on all subsequent API requests

---

## Local Development

Each package has its own setup guide. The recommended order is:

1. **API** — start DDEV, run migrations, configure MinIO for local object storage
2. **Frontend** — configure `VITE_API_URL` to point at the local API, run `npm run dev`
3. **Landing** — configure `VITE_API_URL` and `VITE_APP_URL`, run `npm run dev`

Both Vue apps use an HTTPS dev server; see the respective READMEs for certificate setup.

---

## Deployment

| Package | Platform | Notes |
|---|---|---|
| `api` | Any PHP 8.4 host | Requires a MySQL/PostgreSQL database and an S3-compatible object store |
| `frontend` | Cloudflare Pages | Root directory: `frontend`, build command: `npm run build`, output: `dist` |
| `landing` | Cloudflare Pages | Root directory: `landing`, build command: `npm run build`, output: `dist` |

When deploying from this monorepo on Cloudflare Pages, configure **include paths** per project (`frontend/**` and `landing/**` respectively) so that changes to unrelated packages do not trigger unnecessary builds.

---

## Contributing

Pull requests are welcome. Because all three packages live in this repository, a single PR can cover a full feature end-to-end — from API route to frontend UI. Please open an issue first for larger changes so the approach can be discussed before implementation.

---

## License

[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](frontend/LICENSE)

MIT
