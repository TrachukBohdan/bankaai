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

## 6. Files for which AI involvement is high

- All Dockerfiles, `docker-compose.yml`, `docker/nginx/default.conf`
- `api/app/Http/Controllers/StatusController.php`
- `api/routes/api.php`, `api/config/cors.php`, `api/bootstrap/app.php` patch
- `ui/src/lib/api.ts`, `ui/src/stores/status.ts`,
  `ui/src/components/StatusCard.vue`, `ui/src/views/HomeView.vue`,
  `ui/vite.config.ts`, `ui/env.d.ts`
- `README.md`, `.env.example`, `.gitignore`, `docs/task.md` (translation),
  this file

Files mostly untouched from the upstream Laravel / `create-vue` scaffolds are
not listed here.
