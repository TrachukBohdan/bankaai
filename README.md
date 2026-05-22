# BankaAi

Monorepo for the BankaAi test assignment.

- `api/` — Laravel 13 REST API (PHP 8.4, php-fpm) with Sanctum SPA cookie auth,
  per-15-minute polling of MinFin + NBU rates, daily refresh of banks +
  branches, significant-change detection (≥ 5%), email alerts via subscriptions.
- `ui/` — Vue 3 + Vite SPA (TypeScript, [PrimeVue](https://primevue.org/) Aura theme,
  Pinia, Vue Router, Axios) with Leaflet map and Chart.js for statistics.
- `docker/` — Dockerfiles and Nginx config used by `docker-compose.yml` and
  `docker-compose.prod.yml`.
- `docs/` — Task description and AI usage log (`LLM_INSTRUCTIONS.md`).

The full task is in [docs/task.md](docs/task.md).

## Architecture

A single Nginx container is the only public entry point. It splits traffic
between the Laravel backend (FastCGI to php-fpm) and the Vite dev server
(reverse-proxy with WebSocket upgrade for HMR), so the browser only ever
talks to one origin — which also eliminates CORS during development.

```
Browser  ───────►  http://localhost:8080  ──►  nginx
                                                │
            /api/*, /sanctum/*, /up, *.php ─────┤──►  api  (php-fpm :9000)  ──►  db  (mysql :3306)
                                                │
            everything else (incl. WS HMR) ─────┴──►  ui   (vite dev :5173)

   ┌───────────────────────────────────────────────────────────────────────┐
   │ scheduler  (php artisan schedule:work)  → dispatches sync jobs every  │
   │                                            15 min / daily             │
   │ queue      (php artisan queue:work)     → executes jobs, sends mail   │
   │ mailpit    (smtp :1025, ui :8025)       → catches outgoing email      │
   └───────────────────────────────────────────────────────────────────────┘
```

## Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (or Docker Engine + Compose v2).

That's it. PHP, Composer, Node and npm are **not** required on the host — everything runs in containers.

## Quick start

**Recommended:**

```bash
cp .env.example .env && cp api/.env.example api/.env
make dev_build
docker compose up -d
docker compose exec api php artisan migrate --seed
```

Or use `make dev_start`, which copies the env files, runs `docker compose up` in the **foreground** (logs attached), then migrate + seed. Press Ctrl+C to stop; re-run with `docker compose up -d` for a detached stack.

First-time setup: set `APP_KEY` in `.env` if migrate fails:

```bash
docker compose run --rm --no-deps api php artisan key:generate --show
```

Seed contents: currencies, five banks, synthetic 3-month rate history (`ExchangeRateSeeder`), ~29k branches from `api/database/data/branches.json`, then synchronous `SyncBanksJob` for live finance.ua metadata.

**Refresh data from fixtures or live upstreams:**

```bash
docker compose exec api php artisan branches:import-json
docker compose exec api php artisan rates:sync --sync
docker compose exec api php artisan banks:sync --sync
docker compose exec api php artisan branches:sync --sync
```

Then open:

- UI:               <http://localhost:8080>
- API ping:         <http://localhost:8080/api/ping>
- Mailpit (email):  <http://localhost:8025>
- Health probe:     <http://localhost:8080/up>

A demo account is pre-seeded:

- **Email:** `demo@bankaai.test`
- **Password:** `password`

## Services

| Service     | Image / build                                       | Host port      | Internal       |
|-------------|-----------------------------------------------------|----------------|----------------|
| `nginx`     | `nginx:1.27-alpine`                                 | `8080`         | `nginx:80`     |
| `api`       | built from `docker/api/Dockerfile` (php:8.4-fpm)    | —              | `api:9000`     |
| `ui`        | built from `docker/ui/Dockerfile` (node:20-alpine)  | `5173`         | `ui:5173`      |
| `queue`     | reuses `bankaai/api:dev`, `queue:work`              | —              | —              |
| `scheduler` | reuses `bankaai/api:dev`, `schedule:work`           | —              | —              |
| `db`        | `mysql:8.0`                                         | `3306`         | `db:3306`      |
| `mailpit`   | `axllent/mailpit:latest`                            | `8025`         | `mailpit:1025` |

The browser hits the API and the SPA through `nginx` on `http://localhost:8080`; the SPA reads its API base URL from `VITE_API_URL` (see `ui/.env.development`). The Vite dev server is **also** exposed directly on `localhost:5173` so the HMR WebSocket goes browser ↔ Vite without traversing nginx — this avoids `permessage-deflate` / `RSV1` issues that the WebSocket proxy can introduce and keeps a Vite WS crash from cascading through the proxy.

## Makefile

The root `Makefile` wraps common dev and prod workflows. It uses `.env` (copied from the matching `*.example` file) for Compose variable substitution.

| Target            | What it does                                                                  |
|-------------------|-------------------------------------------------------------------------------|
| `make dev_start`  | Dev: copy env templates, `docker compose up` (foreground), migrate + seed   |
| `make dev_build`  | Dev: `docker compose build`                                                   |
| `make dev_reset`  | Dev: `docker compose down -v` (wipes DB + named volumes)                      |
| `make prod_start` | Prod: copy env templates, `up -d`, `db:seed` (migrations run in api entrypoint) |
| `make prod_build` | Prod: build `bankaai-api` and `bankaai-web` images locally                    |
| `make prod_push`  | Prod: push images to GHCR (after `make prod_login`)                           |
| `make prod_pull`  | Prod: pull images from GHCR                                                   |
| `make prod_reset` | Prod: `docker compose … down -v`                                              |
| `make prod_login` | `docker login ghcr.io` (requires `GITHUB_TOKEN` in the environment)           |
| `make prod_logout`| `docker logout ghcr.io`                                                       |

`prod_login` uses the GitHub user `TrachukBohdan`; `IMAGE_NAMESPACE` in `.env` must be the **lowercase** GHCR owner (e.g. `trachukbohdan`).

## Common commands

```bash
# Tail logs for a single service
docker compose logs -f nginx
docker compose logs -f api
docker compose logs -f ui
docker compose logs -f db

# Run artisan inside the API container
docker compose exec api php artisan migrate
docker compose exec api php artisan make:model Bank -m
docker compose exec api php artisan tinker

# Run composer inside the API container
docker compose exec api composer require some/package
docker compose exec api composer dump-autoload

# Run npm inside the UI container
docker compose exec ui npm install some-package
docker compose exec ui npm run lint
docker compose exec ui npm run build

# Reload nginx after editing docker/nginx/default.conf
docker compose exec nginx nginx -s reload

# Open a MySQL shell
docker compose exec db mysql -ubankaai -psecret bankaai

# Stop everything (keeps volumes / data)
docker compose down

# Stop AND wipe the database + named volumes (full reset)
make dev_reset
```

## Project layout

```text
BankaAi/
├── api/                       Laravel application (bind-mounted in dev)
│   └── database/data/         Committed fixtures (e.g. branches.json from finance.ua)
├── ui/                        Vue 3 + PrimeVue + Vite (bind-mounted in dev)
├── docker/
│   ├── api/Dockerfile         Dev php-fpm image
│   ├── api/prod.Dockerfile    Multi-stage production API image
│   ├── api/entrypoint.prod.sh Wait for DB, migrate, optimize, then php-fpm
│   ├── api/scheduler-entrypoint.sh  Initial sync + schedule:work
│   ├── ui/Dockerfile          node:20-alpine dev server
│   ├── web/Dockerfile         Multi-stage nginx + built SPA (prod)
│   ├── nginx/default.conf     Dev gateway (FastCGI + Vite proxy)
│   └── web/default.conf       Prod gateway (static assets + FastCGI)
├── docker-compose.yml         Dev: db, api, queue, scheduler, mailpit, ui, nginx
├── docker-compose.prod.yml      Prod: db, api, queue, scheduler, web (GHCR images)
├── Makefile                   dev_start / prod_start / build / push helpers
├── .env.example               Dev compose overrides (WEB_PORT, DB_*, APP_KEY)
├── .env.prod.example          Prod compose overrides (registry, secrets)
├── docs/
│   ├── task.md                Original task description
│   └── LLM_INSTRUCTIONS.md    Log of AI usage (prompts, models, hand-edits)
└── README.md                  You are here
```

## Production

A separate, image-based stack lives next to the dev one — `docker-compose.prod.yml` plus matching Dockerfiles in `docker/api/prod.Dockerfile` and `docker/web/Dockerfile`. No bind mounts, no dev dependencies, no Vite dev server.

```
Browser  ──►  http://your-host:WEB_PORT  ──►  web (nginx + built JS/CSS)
                                                │
            /api, /sanctum, /up  ──── FastCGI ──┴──►  api (php-fpm 8.4 + Laravel)  ──►  db (mysql:8.0)
            /, /assets/*.js, *.css, …  ─────  served as static files by nginx
```

What changes vs dev:

| Aspect          | Dev (`docker-compose.yml`)                       | Prod (`docker-compose.prod.yml`)                                  |
|-----------------|--------------------------------------------------|--------------------------------------------------------------------|
| API container   | `php:8.4-fpm` + bind mount + `composer install` on the fly | `php:8.4-fpm` with full source baked, `composer install --no-dev --classmap-authoritative`, OPcache on with `validate_timestamps=0` |
| UI container    | `node:20-alpine` running `vite dev` (HMR)        | None at runtime — the Vue app is built once and shipped as static files inside the web image |
| Web container   | `nginx:1.27-alpine` reverse-proxying `vite dev`  | `nginx:1.27-alpine` serving `/usr/share/nginx/html` directly with long-cache headers + FastCGI to `api:9000` |
| Migrations      | Manual via `artisan migrate`                     | Auto on container start (entrypoint waits for the DB, then runs `migrate --force` and `artisan optimize`) |
| Bind mounts     | `./api`, `./ui` (live edits)                     | None — image is immutable                                          |
| Background jobs | `queue` + `scheduler` containers (15 min rates, daily banks/branches) | Same: `queue` + `scheduler` services using the API image            |
| Exposed ports   | `8080`, `5173`, `3306`                           | Only `WEB_PORT` (defaults to `80`); `db` is internal               |
| `APP_ENV`       | `local`                                          | `production`                                                       |
| Image tags      | Local `bankaai/api:dev`, `bankaai/ui:dev`        | `ghcr.io/<owner>/bankaai-api:<tag>`, `ghcr.io/<owner>/bankaai-web:<tag>` (owner lowercase) |

### 1. Configure

```bash
cp .env.prod.example .env
# Edit .env and set at least:
#   IMAGE_NAMESPACE=trachukbohdan   # GHCR owner, lowercase
#   APP_KEY=base64:...              # see step 2
#   APP_URL=https://your-domain
#   DB_PASSWORD, DB_ROOT_PASSWORD (strong passwords)
```

`.env` is gitignored.

### 2. Generate `APP_KEY` once

```bash
docker compose --env-file .env -f docker-compose.prod.yml \
    run --rm --no-deps api php artisan key:generate --show
```

Paste the printed `base64:...` value into `APP_KEY` in `.env`.

### 3. Build locally

```bash
make prod_build
# or: docker compose --env-file .env -f docker-compose.prod.yml build
```

This produces two images tagged according to `REGISTRY` / `IMAGE_NAMESPACE` / `IMAGE_TAG`:

- `<REGISTRY>/<IMAGE_NAMESPACE>/bankaai-api:<IMAGE_TAG>`
- `<REGISTRY>/<IMAGE_NAMESPACE>/bankaai-web:<IMAGE_TAG>`

### 4. Push to GitHub Container Registry

```bash
# One-time auth — PAT with write:packages, or fine-grained "Read and write" on Packages
export GITHUB_TOKEN=<token>
make prod_login
make prod_push
```

### 5. Pull and run anywhere

On the deployment host (only Docker required, nothing else):

```bash
git clone https://github.com/<owner>/BankaAi.git && cd BankaAi
cp .env.prod.example .env   # then edit secrets as above

make prod_pull
make prod_start
# prod_start also runs db:seed — use only on first deploy or when you want demo data reset

docker compose --env-file .env -f docker-compose.prod.yml ps
docker compose --env-file .env -f docker-compose.prod.yml logs -f api
```

The first start automatically waits for MySQL, runs `php artisan migrate --force`, then `php artisan optimize` before php-fpm.

### 6. Publish images to GHCR

Images are built and pushed **locally** (no CI workflow in the repo):

```bash
export GITHUB_TOKEN=<pat-with-write:packages>
make prod_login
make prod_build
make prod_push
```

Tag with `IMAGE_TAG` in `.env` (default `latest`). Pull on another host with `make prod_pull`.

### Useful prod commands

```bash
# Re-run migrations explicitly (also runs on every api container start, but harmless)
docker compose --env-file .env -f docker-compose.prod.yml \
    exec api php artisan migrate --force

# Drop into the api container
docker compose --env-file .env -f docker-compose.prod.yml exec api sh

# Clear Laravel caches (optimize re-runs on next api start)
docker compose --env-file .env -f docker-compose.prod.yml \
    exec api php artisan optimize:clear

# Full reset (wipes the db_data volume)
make prod_reset
```

## Features

- **Five banks** (PrivatBank, Oschadbank, PUMB, Raiffeisen Bank, Ukreximbank) seeded with the two slugs they use in MinFin and finance.ua. The `SyncBanksJob` enriches them daily with logo, legal address, phone, email, license number.
- **Periodic sync** (task §2) — `scheduler` runs `php artisan schedule:work`; `queue` executes jobs. Schedule in `api/routes/console.php`:
  - `rates:sync` every 15 min → `SyncMinFinRatesJob` + `SyncNbuRatesJob` (MinFin cash/card + NBU official for USD/EUR/GBP/CHF/PLN).
  - `banks:sync` daily at 02:00 → finance.ua directory metadata.
  - `branches:sync` daily at 02:15 → finance.ua branch lists per bank.
  - Manual: `php artisan rates:sync|banks:sync|branches:sync` (add `--sync` to run inline).
- **Significant changes** — every imported rate fires a `RateImported` event. `DetectAndAnnounceChange` (queued listener) compares against the previous reading; anything moving more than 5% (configurable via `RATES_SIGNIFICANT_THRESHOLD_PCT`) is stored in `rate_changes` and a `SignificantRateChange` notification is queued via mail + database channels for every matching subscriber.
- **Subscriptions** — authenticated users can subscribe to any (bank, currency) pair with their own threshold. Nullable `bank_id`/`currency_id` mean "any". Notifications respect the user's global `notifications_enabled` toggle.
- **Nearest branches** — MySQL 8 spatial index (POINT, SRID 4326, generated from `lat`/`lng`) + `ST_Distance_Sphere`. Dev seed loads ~29k rows from `api/database/data/branches.json` (finance.ua snapshot); `branches:sync` refreshes from the live API daily.
- **REST API** (prefix `/api`): `GET /ping`, `/status`, `/currencies`, `/banks`, `/banks/{slug}`, `/rates`, `/rates/nbu`, `/rates/history`, `/rates/statistics`, `/rates/changes`, `/branches/nearest`. Auth (Sanctum cookies): `POST /auth/register`, `/auth/login`; `POST /auth/logout`, `GET|PUT /me`, `GET|POST /me/subscriptions`, `DELETE /me/subscriptions/{id}`.
- **Frontend** (Vue 3 + PrimeVue): `/` home, `/banks`, `/banks/:slug` (branch map), `/rates` (table + NBU averages), `/nearest` (geolocation + Leaflet), `/statistics` (date range + Chart.js), `/login`, `/register`, `/profile` (subscriptions + notification toggle). Shared `LoadingBlock` / `ErrorBlock` components; Leaflet marker icons via `ui/src/lib/leafletIcons.ts`.

## Tests

```bash
# Runs the full PHPUnit suite (unit + feature) against the in-memory SQLite DB
docker compose exec api php artisan test

# Type-check the frontend
docker compose exec ui npx vue-tsc --noEmit -p tsconfig.app.json

# PHP code style (auto-fix)
docker compose exec api ./vendor/bin/pint
```

The migration that adds the MySQL 8 spatial column is wrapped in a driver
check, so the in-memory SQLite test DB skips it cleanly.

## Notes on the current scope

- Dev stack is feature-complete: API + UI + scheduler + queue + mailpit, with HMR on a direct Vite port.
- Prod stack ships static SPA assets + an immutable Laravel image, auto-migrations on api boot (`entrypoint.prod.sh`), **`queue` + `scheduler`** for periodic upstream sync, and GHCR publish via `make prod_build` / `make prod_push`. Use `make dev_start` or the detached flow above for local dev.
- Mailpit catches every outgoing email in dev; in prod swap to a real `MAIL_*` configuration (or pull e.g. `mailhog/mailhog`).
- No TLS termination — typically handled by a reverse proxy (Caddy, Traefik, an upstream load balancer) in front of `web` in real deployments.
