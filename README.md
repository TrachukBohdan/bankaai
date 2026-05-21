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

```bash
# 1. (Optional) copy env defaults; the compose file works without this step
cp .env.example .env

# 2. Build images and start everything
docker compose up --build -d

# 3. Run migrations and seed (includes finance.ua bank metadata sync)
docker compose exec api php artisan migrate --seed

# 4. Populate live data from the upstreams (one-time backfill; the scheduler will keep it fresh)
docker compose exec api php artisan banks:sync
docker compose exec api php artisan tinker --execute='
  dispatch_sync(new App\Jobs\SyncNbuRatesJob);
  dispatch_sync(new App\Jobs\SyncMinFinRatesJob);
  dispatch_sync(new App\Jobs\SyncBranchesJob);
'
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
docker compose down -v
```

## Project layout

```text
BankaAi/
├── api/                       Laravel application (bind-mounted into api and nginx containers)
├── ui/                        Vue 3 + PrimeVue + Vite (bind-mounted into the ui container)
├── docker/
│   ├── api/Dockerfile         php:8.4-fpm + extensions (pdo_mysql, mbstring, bcmath, zip, intl) + composer
│   ├── ui/Dockerfile          node:20-alpine + npm install
│   ├── nginx/default.conf     FastCGI for Laravel, reverse-proxy + WS upgrade for Vite
│   └── mysql/                 Reserved for MySQL init scripts / my.cnf when needed
├── docker-compose.yml         Four services: db, api, ui, nginx (single bankaai_net bridge network)
├── .env.example               Overridable WEB_PORT, DB_PORT, DB credentials
├── docs/
│   ├── task.md                Original task description
│   └── LLM_INSTRUCTIONS.md    Log of AI usage (which prompts, which model, what was hand-edited)
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
| Exposed ports   | `8080`, `5173`, `3306`                           | Only `WEB_PORT` (defaults to `80`); `db` is internal               |
| `APP_ENV`       | `local`                                          | `production`                                                       |
| Image tags      | Local `bankaai/api:dev`, `bankaai/ui:dev`        | `ghcr.io/<owner>/bankaai-api:<tag>`, `ghcr.io/<owner>/bankaai-web:<tag>` |

### 1. Configure

```bash
cp .env.prod.example .env.prod
# Edit .env.prod and set at least:
#   IMAGE_NAMESPACE=<your-github-username-or-org>
#   APP_KEY=base64:...        # see step 2
#   APP_URL=https://your-domain
#   DB_PASSWORD, DB_ROOT_PASSWORD (strong passwords)
```

`.env.prod` is gitignored.

### 2. Generate `APP_KEY` once

```bash
docker compose --env-file .env.prod -f docker-compose.prod.yml \
    run --rm --no-deps api php artisan key:generate --show
```

Paste the printed `base64:...` value into `APP_KEY` in `.env.prod`.

### 3. Build locally

```bash
docker compose --env-file .env.prod -f docker-compose.prod.yml build
```

This produces two images tagged according to `REGISTRY` / `IMAGE_NAMESPACE` / `IMAGE_TAG`:

- `<REGISTRY>/<IMAGE_NAMESPACE>/bankaai-api:<IMAGE_TAG>`
- `<REGISTRY>/<IMAGE_NAMESPACE>/bankaai-web:<IMAGE_TAG>`

### 4. Push to GitHub Container Registry

```bash
# One-time auth — use a Personal Access Token (classic) with the `write:packages`
# scope, or a fine-grained token with "Read and write" on Packages for the target
# user/org. See https://docs.github.com/en/packages/working-with-a-github-packages-registry/working-with-the-container-registry
echo "<GHCR_TOKEN>" | docker login ghcr.io -u <your-github-username> --password-stdin

docker compose --env-file .env.prod -f docker-compose.prod.yml push
```

### 5. Pull and run anywhere

On the deployment host (only Docker required, nothing else):

```bash
git clone https://github.com/<owner>/BankaAi.git && cd BankaAi  # for the compose file
cp .env.prod.example .env.prod  # then edit secrets as above

docker compose --env-file .env.prod -f docker-compose.prod.yml pull
docker compose --env-file .env.prod -f docker-compose.prod.yml up -d

docker compose --env-file .env.prod -f docker-compose.prod.yml ps
docker compose --env-file .env.prod -f docker-compose.prod.yml logs -f api
```

The first start automatically waits for MySQL, runs `php artisan migrate --force`, then `php artisan optimize` before php-fpm.

### 6. Automated builds via GitHub Actions

`.github/workflows/publish-images.yml` builds both images for `linux/amd64` and `linux/arm64` and pushes them to GHCR on every push to `main`, on every `v*.*.*` tag, and on manual `workflow_dispatch`. It uses the built-in `GITHUB_TOKEN`; no extra secrets needed — just enable `Settings → Actions → General → Workflow permissions → Read and write` once.

Common tags it produces:
- `main` — every push to the default branch
- `latest` — alias for the head of the default branch
- `sha-<short>` — every commit
- `v1.2.3`, `1.2`, `1.2.3` — when you push a `v*.*.*` git tag

Verify after a run by listing your packages on the GitHub UI (`/<owner>?tab=packages`) or:

```bash
docker pull ghcr.io/<owner>/bankaai-api:latest
docker pull ghcr.io/<owner>/bankaai-web:latest
```

### Useful prod commands

```bash
# Re-run migrations explicitly (also runs on every container start, but harmless)
docker compose --env-file .env.prod -f docker-compose.prod.yml \
    exec api php artisan migrate --force

# Drop into the api container
docker compose --env-file .env.prod -f docker-compose.prod.yml exec api sh

# Clear Laravel caches (optimize re-runs on next start)
docker compose --env-file .env.prod -f docker-compose.prod.yml \
    exec api php artisan optimize:clear

# Full reset (will wipe the db_data volume!)
docker compose --env-file .env.prod -f docker-compose.prod.yml down -v
```

## Features

- **Five banks** (PrivatBank, Oschadbank, PUMB, Raiffeisen Bank, Ukreximbank) seeded with the two slugs they use in MinFin and finance.ua. The `SyncBanksJob` enriches them daily with logo, legal address, phone, email, license number.
- **Rates ingestion**:
  - `SyncMinFinRatesJob` every 15 min — pages through `minfin.com.ua/api/currency/rates/banks/{cc}` for USD/EUR/GBP/CHF/PLN, capturing both `cash` and `card` markets.
  - `SyncNbuRatesJob` every 15 min — pulls the NBU official rate for the same currencies.
- **Significant changes** — every imported rate fires a `RateImported` event. `DetectAndAnnounceChange` (queued listener) compares against the previous reading; anything moving more than 5% (configurable via `RATES_SIGNIFICANT_THRESHOLD_PCT`) is stored in `rate_changes` and a `SignificantRateChange` notification is queued via mail + database channels for every matching subscriber.
- **Subscriptions** — authenticated users can subscribe to any (bank, currency) pair with their own threshold. Nullable `bank_id`/`currency_id` mean "any". Notifications respect the user's global `notifications_enabled` toggle.
- **Nearest branches** — MySQL 8 spatial index (POINT, SRID 4326, generated from `lat`/`lng`) + `ST_Distance_Sphere`. ~2.5k branches across the 5 banks, refreshed daily.
- **REST API**: `/api/currencies`, `/api/banks`, `/api/banks/{slug}`, `/api/rates`, `/api/rates/nbu` (rates + per-currency average across banks), `/api/rates/statistics`, `/api/rates/changes`, `/api/branches/nearest`. Auth via Sanctum cookies: `/api/auth/register`, `/api/auth/login`, `/api/auth/logout`, `/api/me`, `/api/me/subscriptions`.
- **Frontend** — fully implemented views: Home (NBU + bank average highlights), Banks list, Bank detail (with map of branches), Rates (filterable), NBU + averages, Nearest branches (Leaflet map + table, with `navigator.geolocation`), Statistics (Chart.js daily line + min/max/avg), History of significant changes, Login/Register, Profile + subscription management.

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

- Dev stack is feature-complete: API + UI + scheduler + queue + mailpit are running side-by-side with HMR.
- Prod stack ships static SPA assets + an immutable Laravel image, with auto-migrations on boot and ready-to-push image tags. The same `bankaai/api:dev` image is reused for `queue` and `scheduler` services so there's only one runtime build target.
- Mailpit catches every outgoing email in dev; in prod swap to a real `MAIL_*` configuration (or pull e.g. `mailhog/mailhog`).
- No TLS termination — typically handled by a reverse proxy (Caddy, Traefik, an upstream load balancer) in front of `web` in real deployments.
