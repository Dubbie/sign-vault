# SignVault

[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)
[![Vue](https://img.shields.io/badge/Vue-3-42B883?logo=vue.js&logoColor=white)](https://vuejs.org/)
[![TypeScript](https://img.shields.io/badge/TypeScript-5-3178C6?logo=typescript&logoColor=white)](https://www.typescriptlang.org/)
[![Last commit](https://img.shields.io/github/last-commit/Dubbie/sign-vault)](https://github.com/Dubbie/sign-vault/commits/main)
[![Issues](https://img.shields.io/github/issues/Dubbie/sign-vault)](https://github.com/Dubbie/sign-vault/issues)

SignVault is a Trackmania sign hosting and sharing platform. It lets players upload sign assets, organize them into folders, share public collections, browse community folders with previews, and manage stable URLs for in-game use.

The project is a monorepo with a Laravel API, a Vue application for the main product, and a Vue landing page. All three deploy independently but share the same domain model and release version.

## Highlights

- Multi-provider OAuth login with Discord and Trackmania / Ubisoft
- Personal folders with `private`, `public`, and password-protected visibility
- Public explore flow with preview grids, attribution, sorting, and folder voting
- Folder variants for alternate sign sets without duplicating the folder
- Admin moderation tools for users, folders, signs, and activity logs
- Utility pages for sign sizing and Trackmania name tag formatting
- Repo-wide changelog and release tags managed through `release-please`

## Monorepo Structure

```text
sign-vault/
├── api/         # Laravel 13 REST API
├── frontend/    # Vue 3 authenticated app
└── landing/     # Vue 3 marketing and live stats site
```

## Packages

### `api`

The backend owns authentication, folder/sign/variant management, public browsing, moderation, and release-adjacent metadata like live platform stats.

Current responsibilities include:

- OAuth redirect/callback flows for `discord` and `trackmania`
- Linked-provider account management
- Sign uploads to S3-compatible object storage
- Public folder browsing, password unlock flow, and voting
- Variant management for folder-specific sign sets
- Admin browse/moderation endpoints and activity log retrieval

See [`api/README.md`](api/README.md) for API setup details.

### `frontend`

The main Vue app handles the full authenticated product experience plus the public explore flow.

Current product areas include:

- Login and OAuth callback handling for both providers
- Public explore and public folder views
- Personal folder creation, editing, and sharing
- Upload, move, delete, and variant assignment flows for signs
- Settings, linked-provider management, and avatar/profile editing
- Admin dashboards for users, content moderation, and activity logs
- Utility tools for sign sizing and name tag formatting
- Footer version badge with modal release notes sourced from `CHANGELOG.md`

See [`frontend/README.md`](frontend/README.md) for frontend setup details.

### `landing`

The landing app is the public-facing marketing site. It introduces the product, surfaces live stats from the API, and funnels users into login or browsing.

Current landing content includes:

- Hero and product overview
- Live stats for users, signs, uptime, and latency
- Feature callouts for browsing, sharing, and authentication
- Links to the main app, source code, Discord, and legal pages

See [`landing/README.md`](landing/README.md) for landing setup details.

## Authentication

SignVault does not use local email/password accounts. Authentication is handled through external OAuth providers.

Supported providers today:

- Discord
- Trackmania / Ubisoft

High-level flow:

1. The frontend requests `GET /api/auth/{provider}/redirect`
2. The API returns a provider authorization URL and state token
3. The user authenticates with the selected provider
4. The frontend posts the callback `code` and `state` to `POST /api/auth/{provider}/callback`
5. The API issues a Sanctum bearer token for subsequent authenticated requests

Linked providers can also be added or removed from the authenticated settings flow, with guardrails to prevent removing the last login method.

## Local Development

Recommended startup order:

1. Start the API with DDEV
2. Start the frontend on `https://localhost:5173`
3. Start the landing page on `https://localhost:5174`

Each package has its own setup guide:

- [`api/README.md`](api/README.md)
- [`frontend/README.md`](frontend/README.md)
- [`landing/README.md`](landing/README.md)

Both Vue apps use HTTPS locally so they can talk to the API cleanly during development.

## Deployment

Production deployments are driven from `main`.

| Package | Platform | Notes |
|---|---|---|
| `api` | PHP host / container platform | Requires database + S3-compatible object storage |
| `frontend` | Cloudflare Pages | Auto-deploys on merges to `main`; PR branches get preview deployments |
| `landing` | Cloudflare Pages | Auto-deploys on merges to `main`; PR branches get preview deployments |

For Cloudflare Pages projects in this monorepo:

- `frontend` root directory: `frontend`
- `landing` root directory: `landing`
- build command: `npm run build`
- output directory: `dist`

Configure include paths per Pages project so unrelated monorepo changes do not trigger unnecessary builds.

## Releases

SignVault uses a single repo-wide version across `api`, `frontend`, and `landing`.

- Product code ships continuously from pull requests merged into `main`
- `release-please` watches `main` and opens a dedicated Release PR when releasable commits accumulate
- Merging the Release PR updates the root version, `CHANGELOG.md`, and GitHub release tag
- The frontend version badge and release notes modal are driven from the latest generated changelog entry

Release hygiene:

- Use pull requests for all changes to `main`
- Prefer squash merge
- Keep squash commit titles in conventional-commit format such as `feat(...)`, `fix(...)`, or `chore(...)`

## Contributing

Pull requests are welcome. Because this is a monorepo, a single change can span API, frontend, and landing in one branch.

For larger product or architecture changes, open an issue first so the direction can be discussed before implementation.

## License

MIT
