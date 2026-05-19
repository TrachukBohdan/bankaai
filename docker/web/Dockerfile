# syntax=docker/dockerfile:1.7
#
# Production image for the SPA + edge gateway.
#
# Stage 1 builds the Vue 3 + Vite app to static JS/CSS (no dev tooling in the
# final image). Stage 2 is a slim nginx that serves the built bundle and
# reverse-proxies API traffic to the php-fpm `api` service via FastCGI.
#
# Build context is the repository ROOT (see docker-compose.prod.yml).

# -----------------------------------------------------------------------------
# Stage 1: Vite production build
# -----------------------------------------------------------------------------
FROM node:20-alpine AS builder

WORKDIR /app

# `VITE_API_URL` is baked into the bundle at build time via `import.meta.env`.
# Default `/api` matches the same-origin proxy in default.conf below.
ARG VITE_API_URL=/api
ENV VITE_API_URL=${VITE_API_URL}

# Cache npm install across builds: copy lockfile first, install, then source.
COPY ui/package.json ui/package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY ui/ ./

RUN npm run build \
 && ls -lh dist | head

# -----------------------------------------------------------------------------
# Stage 2: nginx serving the built SPA + FastCGI to api
# -----------------------------------------------------------------------------
FROM nginx:1.27-alpine AS runtime

# Built SPA bundle (hashed assets in /assets, plus index.html, favicon, etc.)
COPY --from=builder /app/dist /usr/share/nginx/html

# Nginx config: SPA fallback for /, FastCGI for /api, /sanctum, /up
COPY docker/web/default.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

# Default CMD inherited from nginx base image: `nginx -g 'daemon off;'`.
