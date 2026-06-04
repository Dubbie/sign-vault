# SignVault Landing

> Trackmania sign hosting and sharing platform — landing page

SignVault is a platform for Trackmania players to upload, organise, and share in-game signs with the community. This is the Vue 3 landing/marketing page, part of the [sign-vault monorepo](https://github.com/Dubbie/sign-vault).

## Features

- **Live server statistics** — Animated counters for total users, signs, uptime, and CDN latency
- **Discord OAuth login** — One-click authentication gateway to the main app
- **Feature highlights** — Preview of browse, sharing, and authentication capabilities
- **Dark theme** — Custom Tailwind CSS v4 dark colour palette with ambient glow effects

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | [Vue 3](https://vuejs.org/) with Composition API |
| Language | [TypeScript](https://www.typescriptlang.org/) |
| Styling | [Tailwind CSS 4](https://tailwindcss.com/) |
| Build | [Vite](https://vitejs.dev/) |

## Requirements

- **Node.js** `^20.19.0` or `>=22.12.0`
- **npm**
- A running instance of the SignVault API (`api/` in the monorepo)

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
VITE_API_URL=https://api.sign-vault.ddev.site/
VITE_APP_URL=https://sign-vault.ddev.site/
```

### 3. Start the dev server

The landing page runs over HTTPS in development to stay consistent with the rest of the stack.

```bash
npm run dev
```

Open [https://localhost:5174](https://localhost:5174). If your browser warns about the local self-signed certificate, accept it for local development.

## Scripts

| Command | Description |
|---|---|
| `npm run dev` | Start the HTTPS Vite dev server |
| `npm run build` | Type-check and build for production |
| `npm run preview` | Preview the production build locally |
