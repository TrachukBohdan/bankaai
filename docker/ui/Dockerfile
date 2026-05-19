# Development image for the Vue 3 + Vite SPA.
# Source code is bind-mounted at runtime via docker-compose; only package files
# are copied here so node_modules is baked into the image and survives the bind
# mount via a named volume (see docker-compose.yml).

FROM node:20-alpine

ENV CI=true \
    npm_config_update_notifier=false \
    npm_config_fund=false \
    npm_config_audit=false

WORKDIR /app

COPY package.json package-lock.json ./

RUN npm install --no-audit --no-fund

EXPOSE 5173

CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0", "--port", "5173"]
