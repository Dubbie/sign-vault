# SignVault Frontend

> Trackmania sign hosting and sharing platform — frontend

[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

SignVault is a platform for Trackmania players to upload, organise, and share in-game signs with the community. This repository contains the Vue 3 frontend application.

**Backend repository:** [Dubbie/sign-vault-backend](https://github.com/Dubbie/sign-vault-backend)

## Features

- **Browse public folders** — Discover sign collections shared by the community
- **Create and manage folders** — Organise your signs into custom collections
- **Share folders** — Public or password-protected sharing options
- **Discord authentication** — Sign in with your Discord account
- **Admin tools** — Moderate content and manage users

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | [Vue 3](https://vuejs.org/) with Composition API |
| Language | [TypeScript](https://www.typescriptlang.org/) |
| Routing | [Vue Router](https://router.vuejs.org/) |
| State | [Pinia](https://pinia.vuejs.org/) |
| Styling | [Tailwind CSS 4](https://tailwindcss.com/) |
| HTTP | [Axios](https://axios-http.com/) |
| Build | [Vite](https://vitejs.dev/) |
| Lint | [Oxlint](https://oxc.rs/) + [ESLint](https://eslint.org/) |
| Test | [Vitest](https://vitest.dev/) |

## Requirements

- **Node.js** `^20.19.0` or `>=22.12.0`
- **npm**
- A running instance of the [SignVault API](https://github.com/Dubbie/sign-vault-backend)

## Getting Started

### 1. Install dependencies

```bash
npm install
```

### 2. Configure environment

```bash
cp .env.example .env
```

Edit `.env` if needed:

```env
VITE_API_URL=http://localhost:8000
```

### 3. Start the dev server

The frontend runs over HTTPS in development so it can communicate with an HTTPS API without mixed-content issues.

```bash
npm run dev
```

Open [https://localhost:5173](https://localhost:5173). If your browser warns about the local self-signed certificate, accept it for local development.

## Scripts

| Command | Description |
|---|---|
| `npm run dev` | Start the HTTPS Vite dev server |
| `npm run build` | Type-check and build for production |
| `npm run preview` | Preview the production build locally |
| `npm run test:unit` | Run unit tests with Vitest |
| `npm run lint` | Run Oxlint and ESLint |
| `npm run format` | Format source with Prettier |

## Authentication

SignVault uses **Discord OAuth2** with bearer-token authentication:

1. The login flow redirects to `GET /api/auth/discord/redirect`
2. Discord redirects back to `/auth/discord/callback` with a `code` and `state`
3. The callback exchanges the code for a token via `POST /api/auth/discord/callback`
4. The token is stored in `localStorage` and sent as a `Bearer` header on subsequent requests
5. Logout calls `POST /api/auth/logout` and clears local state

## Project Structure

| Path | Purpose |
|---|---|
| `src/lib/` | API clients and utilities |
| `src/stores/` | Pinia state stores |
| `src/router/` | Route definitions and navigation guards |
| `src/views/` | Page-level components |
| `src/components/ui/` | Reusable UI primitives |
| `src/components/explore/` | Explore-related components |
| `src/components/folders/` | Folder management components |
| `src/components/signs/` | Sign display and upload components |
| `src/types/` | TypeScript type definitions |
| `src/layouts/` | Layout components |
| `certs/` | Local HTTPS certificates (not committed) |

## License

[MIT](LICENSE)
