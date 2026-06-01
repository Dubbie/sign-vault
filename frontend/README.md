# SignVault Frontend

Vue 3 frontend for SignVault, a Trackmania sign hosting and sharing app.

## Requirements

- Node.js `20.19.0` or newer
- npm
- A running SignVault API

## Setup

Install dependencies:

```bash
npm install
```

Create your local environment file:

```bash
cp .env.example .env
```

Edit `.env` if needed:

```env
VITE_API_URL=http://localhost:8000
```

## Start The App

The frontend runs over HTTPS in development so it can talk to an HTTPS API without mixed-content issues.

Start the dev server:

```bash
npm run dev
```

Then open:

```text
https://localhost:5173
```

If your browser warns about the local certificate, accept it for local development.

## Auth Flow

This frontend uses bearer-token auth for the MVP.

- `/login` starts Discord login by calling `GET /api/auth/discord/redirect`
- `/auth/discord/callback` exchanges the Discord `code` and `state` with `POST /api/auth/discord/callback`
- The returned token is stored in `localStorage`
- `/dashboard` is protected and refreshes the current user with `GET /api/me`
- Logout calls `POST /api/auth/logout` and clears local auth state

## Scripts

- `npm run dev` - start the HTTPS Vite dev server
- `npm run build` - type-check and build for production
- `npm run preview` - preview the production build
- `npm run test:unit` - run Vitest
- `npm run lint` - run Oxlint and ESLint

## Project Structure

- `src/lib/api.ts` - reusable Axios client with bearer token support
- `src/stores/auth.ts` - Pinia auth store
- `src/router/index.ts` - route definitions and auth guards
- `src/views/LoginView.vue` - Discord login page
- `src/views/DiscordCallbackView.vue` - OAuth callback handler
- `src/views/DashboardView.vue` - protected dashboard

## Notes

- The frontend expects `VITE_API_URL` to point at the API origin.
- Local HTTPS certs for development are stored in `certs/` and are not committed.
