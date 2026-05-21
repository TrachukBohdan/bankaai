# LLM Usage Log

This document fulfils the *"Using AI"* requirement in [task.md](task.md). It is a
running log of how Large Language Models were used while building BankaAi, what
human review/refinement was applied on top of the generated output, and which
decisions changed after the first AI generation.

## 1. Tools and models

| Tool                      | Purpose                                            |
|---------------------------|----------------------------------------------------|
| Cursor IDE (Agent mode)   | Driving the assistant; running shell/docker/git/file tools inside the workspace sandbox |
| Cursor IDE (Plan mode)    | Producing a written architecture plan before any code was written (read-only, no edits) |
| Claude Opus 4.7 (Anthropic) | The underlying LLM for every assistant turn in this repository so far |

No other LLM was used. There is no `.cursor/rules` or out-of-band system prompt;
the assistant ran with Cursor's stock agent + plan modes.

## 2. What was generated with AI assistance

Roughly in chronological order:

1. **Translation of the task specification** from Ukrainian to English
   (`docs/task.md`).
2. **Architecture plan** for the base project (saved as a Cursor plan file,
   summarised in [README.md](../README.md)). Covered repo layout, Docker
   image choices, service topology, ports, env wiring.
3. **Laravel 13 scaffolding** into `api/`, executed via a throwaway
   `composer:2` Docker container so PHP/Composer were not required on the host.
4. **Vue 3 + Vite scaffolding** into `ui/` (TypeScript, Vue Router, Pinia,
   ESLint, Prettier) via a throwaway `node:20-alpine` container; `axios` added
   afterwards and `package-lock.json` committed.
5. **Docker configuration**:
   - `docker/api/Dockerfile` — `php:8.4-fpm` with `pdo_mysql`, `mbstring`,
     `bcmath`, `zip`, `intl` and Composer.
   - `docker/ui/Dockerfile` — `node:20-alpine` running `npm run dev`.
   - `docker/nginx/default.conf` — single-port gateway that FastCGI-routes
     `/api/*`, `/sanctum/*`, `/up`, and `*.php` to php-fpm and reverse-proxies
     everything else (with WebSocket upgrade) to Vite.
   - `docker-compose.yml` — four services (`db`, `api`, `ui`, `nginx`),
     healthcheck on MySQL, named volumes for `db_data`, `api_vendor`,
     `ui_node_modules`.
6. **Laravel wiring**:
   - `bootstrap/app.php` — enabled API routing under the `api` prefix.
   - `routes/api.php` — `GET /api/ping` and `GET /api/status`.
   - `config/cors.php` — published manually because Laravel 11+ no longer
     ships it.
   - `api/.env` and `api/.env.example` — MySQL settings, `APP_URL`,
     `FRONTEND_URL`, `APP_NAME=BankaAi`.
   - `app/Http/Controllers/StatusController.php` — typed controller with a live
     `DB::connection()->getPdo()` probe.
7. **Vue wiring**:
   - `ui/vite.config.ts` — reads `VITE_HMR_CLIENT_PORT` via `loadEnv` so HMR
     points at the directly-exposed Vite port.
   - `ui/src/lib/api.ts` — typed axios instance reading `VITE_API_URL`.
   - `ui/src/stores/status.ts` — Pinia store with typed `ApiStatus`, loading
     and `AxiosError`-aware error handling.
   - `ui/src/components/StatusCard.vue` — Composition API + `<script setup>`,
     scoped CSS, accessible refresh button.
   - `ui/src/views/HomeView.vue` — mounts `<StatusCard />` above the default
     welcome content.
8. **Root files** — `README.md`, `.env.example`, `.gitignore`.

## 3. Notable prompts driven by the human reviewer

Quoted verbatim (paraphrased only where shortened for readability). Each turn
was sent to Claude Opus 4.7 inside Cursor.

| # | Prompt summary                                                                                   | Mode  |
|---|---------------------------------------------------------------------------------------------------|-------|
| 1 | "Translate the attached Ukrainian task spec into English and write it to `docs/task.md`."         | Ask   |
| 2 | "I want to use PHP with Laravel latest and Vue 3. Analyse the requirements and propose a base-project plan: where the backend lives, where the frontend lives, what docker-compose setup we need, which images to pull, etc. I expect separate folders for api, ui and docker." | Plan |
| 3 | "Implement the plan as specified. The plan is attached for your reference. Do not edit the plan file itself. The to-dos already exist; mark them in_progress as you work and don't stop until they're all done." | Agent |
| 4 | "Please continue."                                                                                | Agent |
| 5 | "Please add nginx to serve requests and frontend."                                                | Agent |
| 6 | Pasted Vite log: `RangeError: Invalid WebSocket frame: RSV1 must be clear`                        | Agent |
| 7 | Linked Stack Overflow #77296871 and asked whether the answer applied here                         | Agent |
| 8 | "`http://localhost:8080/node_modules/.vite/deps/pinia.js?v=6abb1629` 403 Forbidden"               | Agent |
| 9 | "Make simple test controller and display data from it in ui."                                     | Agent |
|10 | "Update `docs/LLM_INSTRUCTIONS.md`."                                                              | Agent |

Before generating the plan in prompt #2 the assistant asked two clarifying
multiple-choice questions via Cursor's question UI:

- **Database** — human chose **MySQL 8** over PostgreSQL / MariaDB.
- **Scope of the initial scaffold** — human chose **"Minimal: api + ui + db
  only"**, which was later widened in prompt #5 when the human asked for Nginx.

## 4. Decisions that changed after the initial generation

| Initial AI output                                            | Reason it changed                                                                                                          | Final decision                                                  |
|--------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------|-----------------------------------------------------------------|
| API container based on `php:8.3-cli` with `artisan serve`    | `composer install` failed during the first build — Laravel 13's lockfile depends on Symfony 8 packages that require PHP ≥ 8.4 | Bumped base image to `php:8.4-cli`, then later to `php:8.4-fpm` |
| `php:8.4-cli` + `artisan serve`, no Nginx (minimal scope)     | Human reviewer (prompt #5) asked for production-style Nginx fronting both API and frontend                                  | Switched API to `php:8.4-fpm` on `:9000`, added `nginx:1.27-alpine` as the single public entry on `:8080`, kept `vite dev` for the UI and reverse-proxied it through Nginx with `Upgrade` headers |
| Included the `gd` PHP extension in the API Dockerfile         | `gd` configure step failed with `zlib >= 1.2.11 not found` on the PHP 8.4 base image; the task doesn't need image manipulation | Dropped `gd` (and its `zlib1g-dev`/`libfreetype6-dev`/`libjpeg-dev`/`libpng-dev` apt deps) entirely                                  |
| Apt install step ran without retries                          | Transient Debian-mirror hash mismatch on `git-man` broke the build (`E: Failed to fetch ... Hash Sum mismatch`)             | Wrapped `apt-get install` in a 3-attempt retry loop with `Acquire::Retries=5` and `Acquire::http::No-Cache=true`                    |
| HMR WebSocket proxied through Nginx                           | Vite v8.0.13's bundled `ws` v8 crashed with `RangeError: Invalid WebSocket frame: RSV1 must be clear` under the proxy, killing the whole Vite process and invalidating its `?v=` dep-hashes (which then surfaced as 403s on `/node_modules/.vite/deps/...`) | First attempt: stripped `Sec-WebSocket-Extensions` at the proxy to disable `permessage-deflate`. When that turned out not to be enough, exposed `ui:5173` directly on the host and pointed `VITE_HMR_CLIENT_PORT` at it so HMR bypasses Nginx altogether |
| Nginx "hide hidden files" rule: `location ~ /\.(?!well-known).*` | Pattern was not anchored, so it also matched the `.vite` segment inside `/node_modules/.vite/deps/...` and returned 403 for Vite's pre-bundled deps | Anchored with `^`: `location ~ ^/\.(?!well-known).*` so only top-level dot-paths are denied (`/.git`, `/.env`, …)                |
| `routes/api.php` started with an inline `Route::get('/ping', fn() => …)` only | Wanted a real controller class (the task asks for production-level quality) | Added `App\Http\Controllers\StatusController`, kept `/ping` as a one-liner for the smoke test, and added `/status` backed by the controller |

## 5. What was refined manually

This repo has been built collaboratively. So far the assistant generated the
files; the human reviewer drove direction and provided real-world feedback that
the AI could not have produced on its own. Specifically:

- **Architectural choices via Cursor's multiple-choice prompts** — the human
  picked MySQL over Postgres and chose the initial minimal scope. The assistant
  did not silently assume.
- **Mid-flight scope change** — the human decided to introduce Nginx
  (prompt #5), which triggered a full redesign of the API container from `cli`
  to `fpm`, a new `docker/nginx/default.conf`, and a same-origin model that
  eliminated CORS for dev.
- **Runtime bug reports** — the WebSocket RSV1 crash, the `/.vite/deps/pinia.js`
  403, and an apt-mirror hash mismatch were all observed by the human against a
  running stack and pasted back into the chat. Each became a focused fix that
  the AI alone (without the running container output) could not have produced.
- **Validation of fixes** — every fix in this repo was followed by a real
  `docker compose` build/restart and an `curl` smoke-test from the host, the
  output of which was inspected before moving on.

Going forward, as feature work for the task lands (banks/branches/rates/
notifications), this section will grow to record which specific PHP/Vue files
were rewritten by hand after generation, and any deviations from the
AI-suggested structure.

## 6. Feature pass — May 2026 (full backend + frontend)

The human reviewer asked the assistant to implement the task per `docs/task.md`
following SOLID / YAGNI / KISS / DRY, leaving `// XXX:` / `// TODO:` comments
where assumptions had to be made. That single sentence triggered the
implementation captured in this pass.

### 6.1 Prompts that drove this pass

| # | Prompt summary                                                                                                                                                                                   | Mode  |
|---|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|-------|
| 1 | "[Lines 40-97 of `task.md`] please check and describe how we can implement such requirements and if all information required is provided"                                                          | Ask   |
| 2 | "Please implement it according best practices SOLID, YAGNI, KISS, DRY etc, if something you are not sure enough leave a comment"                                                                   | Plan  |
| 3 | "Yes, please implement this according to a task"                                                                                                                                                   | Agent |

### 6.2 How the AI worked through this pass

1. **Live API probing first**. Before writing any integration code the assistant
   `curl`-ed each upstream once (NBU, MinFin currency list, MinFin
   `rates/banks/usd`, finance.ua `organizationsList`, finance.ua `branches?slug=…`)
   and inspected the JSON shapes. This is what produced the dual-slug design
   for `banks` (one slug for MinFin, one for finance.ua) — without that probe
   the assistant would have assumed slugs matched 1:1 and only `aval` /
   `raiffeisen-bank-aval` would have broken at runtime.
2. **Schema then services then controllers**. Migrations and Eloquent models
   landed first; then the integration layer (`Contracts`, `DTO`, `Services`,
   `Jobs`); then the read-side (`Resources`, `Controllers`, `FormRequests`,
   routes); then auth via Sanctum SPA cookies. The frontend came last and
   reused the typed shapes verbatim in `ui/src/lib/types.ts`.
3. **Verified each layer**. After migrations the assistant ran
   `dispatch_sync(new SyncBanksJob)`, `SyncNbuRatesJob`, `SyncMinFinRatesJob`
   and `SyncBranchesJob` from `tinker` against the real upstreams and printed
   row counts (5 banks enriched, 5 NBU rows, 27 MinFin rows, 2641 branches) to
   confirm the integration pipeline works end-to-end before writing any
   controller. Then every public endpoint was `curl`-tested before the Vue
   work started.
4. **Tests**. The assistant wrote three focused PHPUnit tests
   (`RatesEndpointsTest`, `AuthFlowTest`, `SignificantChangeDetectorTest`).
   The migration was patched to skip the MySQL-only POINT column when the
   driver is `sqlite` so the in-memory test suite can run.

### 6.3 Decisions changed mid-pass

| Initial AI choice                                                                              | Reason                                                                                                                                                                                                                  | Final decision                                                                                                                                                       |
|------------------------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Treat MinFin and finance.ua slugs as identical                                                  | Live probe showed `aval` vs `raiffeisen-bank-aval`, `sensebank` vs `sense-bank`, `credit-agricole` vs `credit-agricole-bank`                                                                                              | `banks` table carries `slug` (canonical, used in our URLs), `minfin_slug`, and `finance_ua_slug` separately; `RateImporter` maintains a slug-alias index over all three |
| Single mid-market `rate` per bank from MinFin                                                   | MinFin returns two markets per bank: `cash` (exchange office) and `card`. Dropping one would lose information already gated by the API                                                                                    | `exchange_rates.market` column distinguishes `cash`/`card`/`official` (NBU); both MinFin markets are kept                                                              |
| RateProvider interface returns `iterable<RateSnapshot>` only                                    | NBU has no bank, MinFin always has one; the importer needs to skip "long tail" banks not in our hand-picked 5                                                                                                            | `RateSnapshot::bankSlug` is `?string`; importer skips rows whose slug isn't in the alias index, logs nothing (expected behaviour for thousands of unmapped MinFin rows)|
| Skip the `coordinates` POINT column to stay portable                                            | Task asks for nearest-branches; without a spatial index this is `O(n)` and 2641 branches today, growing                                                                                                                  | Add a MySQL 8 generated `coordinates POINT … SRID 4326` + spatial index in production; SQLite test connection skips the statement so unit tests stay fast              |
| Sync branches via diff (insert/update/delete by external id)                                    | finance.ua's branch IDs are per (city, position) and not strongly stable. Writing a diff engine would burn time for marginal benefit                                                                                       | YAGNI: `SyncBranchesJob` runs `delete + insert` per bank inside a transaction. Branches are stale at most one daily-sync interval                                      |
| Subscribe via global `everyone` table                                                            | Task asks for "any currency" and "any bank" granularity                                                                                                                                                                  | KISS: `subscriptions` has nullable `bank_id` and `currency_id`; NULL means "any". Detector listener fans out to matching rows                                          |
| Use `php:8.4-fpm` for queue + scheduler too                                                     | Re-using the dev image for queue + scheduler saves a build target; production multi-stage builds still split runtime cleanly                                                                                              | `queue` and `scheduler` services in `docker-compose.yml` are `image: bankaai/api:dev` with different commands. Same image; different entrypoints                       |
| `Auth::guard('web')->login()` + `$request->session()->regenerate()`                            | Worked in dev but failed in PHPUnit because `postJson` does not go through Sanctum's stateful middleware so the request has no session                                                                                   | Wrapped `regenerate`/`invalidate` calls in `if ($request->hasSession())`. The dev SPA still gets a fresh session; tests still register and login successfully           |

### 6.4 Comments left in code where the human should sanity-check

- `app/Services/Integrations/MinFinClient.php` — assumes MinFin's `bid` is bank
  buy and `ask` is bank sell (standard banking convention). Verified visually
  against the response but not against a published MinFin field spec.
- `app/Services/Rates/SignificantChangeDetector.php` — the "previous reading"
  is the immediately-prior row for the same (bank, currency, market, source).
  This is naïve: if MinFin updates twice within the same minute with a stale
  intermediate, we'd evaluate the change against the stale row. Acceptable for
  a 5% threshold; would need windowing for tighter thresholds.
- `database/migrations/2026_05_19_000003_create_branches_table.php` — the
  `coordinates POINT … SRID 4326` column is MySQL 8 specific. Postgres deploys
  would need PostGIS and a different migration.
- `app/Jobs/SyncBranchesJob.php` — wipe-and-replace per bank. If the upstream
  returns an empty `data` array because of a transient outage we skip; we do
  NOT wipe the bank's branches. The check is in the job itself, not in the
  client (the client correctly returns `[]` for HTTP errors after retries).

### 6.5 Files for which AI involvement in this pass is high

Backend (all new or rewritten):

- Migrations under `api/database/migrations/2026_05_19_*` and
  Sanctum/notifications scaffolds
- `api/database/seeders/{Currency,Bank,Database}Seeder.php`
- `api/app/Models/{Bank,Branch,Currency,ExchangeRate,RateChange,Subscription,User}.php`
- `api/app/Contracts/{RateProvider,BankDirectory,BranchDirectory}.php`
- `api/app/DTO/{RateSnapshot,BankRecord,BranchRecord,Coordinates}.php`
- `api/app/Services/Http/HttpJsonClient.php` and
  `api/app/Services/Integrations/{MinFinClient,NbuClient,FinanceUaClient}.php`
- `api/app/Services/Rates/{RateImporter,SignificantChangeDetector,RateStatistics}.php`
- `api/app/Services/Branches/NearestBranchFinder.php`
- `api/app/Jobs/{SyncBanksJob,SyncBranchesJob,SyncMinFinRatesJob,SyncNbuRatesJob}.php`
- `api/app/Events/RateImported.php` +
  `api/app/Listeners/DetectAndAnnounceChange.php` +
  `api/app/Notifications/SignificantRateChange.php`
- `api/app/Http/Controllers/{BankController,BranchController,CurrencyController,RateController}.php`
- `api/app/Http/Controllers/Auth/{RegisterController,LoginController,ProfileController,SubscriptionController}.php`
- `api/app/Http/{Resources,Requests}/*.php`
- `api/app/Providers/AppServiceProvider.php`, `api/bootstrap/app.php`,
  `api/routes/api.php`, `api/routes/console.php`, `api/config/services.php`
- `api/tests/{Unit,Feature}/*` (three new tests)

Frontend (all new or rewritten):

- `ui/src/App.vue`, `ui/src/router/index.ts`, `ui/src/assets/main.css`,
  `ui/index.html`
- `ui/src/lib/{api,auth,geolocation}.ts`, `ui/src/types/api.ts`
- `ui/src/stores/{auth,banks,branches,currencies,rates,status}.ts`
- `ui/src/components/{AppNav,BankCard,NearestBranchesMap,RateFilters,RateHistoryChart,RatesTable,StatusCard}.vue`
- `ui/src/views/{HomeView,BanksListView,BankDetailView,RatesView,NearestView,StatisticsView,LoginView,RegisterView,ProfileView}.vue`

Infra additions:

- `docker-compose.yml` — `queue`, `scheduler`, `mailpit` services; updated
  `SANCTUM_STATEFUL_DOMAINS` and `MAIL_*` env vars on the `api` service.
- `.env.example` — `MAIL_UI_PORT=8025`.
- `api/.env` — `SANCTUM_STATEFUL_DOMAINS`, `SESSION_DOMAIN`, mailpit SMTP,
  `RATES_SIGNIFICANT_THRESHOLD_PCT`.

### 6.6 What was refined by hand in this pass

- `BranchesMap.vue` — initial TS pass had `LatLngTuple | undefined` slipping
  into `setView`. Caught by `vue-tsc` and tightened with a truthy check.
- `RegisterController` — `__invoke` return type was `Response` but the body
  returned `JsonResponse`. Tightened to `JsonResponse` after the test runner
  surfaced the mismatch.
- `Login/Register` controllers — added `if ($request->hasSession())` guards to
  keep both the real SPA and the PHPUnit tests green.
- Branches migration — added MySQL driver guard around the spatial column so
  the in-memory SQLite test connection doesn't fail.

## 7. Catalogue of high-AI-involvement files (cumulative)

- All Dockerfiles, `docker-compose.yml`, `docker-compose.prod.yml`,
  `docker/nginx/default.conf`, `docker/web/default.conf`, `docker/api/*`,
  `.github/workflows/publish-images.yml`
- Every file listed under §6.5 above
- `README.md`, `.env.example`, `.env.prod.example`, `.gitignore`,
  `docs/task.md` (translation), this file

Files mostly untouched from the upstream Laravel / `create-vue` scaffolds are
not listed here.
