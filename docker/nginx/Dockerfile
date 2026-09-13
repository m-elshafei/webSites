# syntax=docker/dockerfile:1.7
ARG NGINX_VERSION=1.30
ARG NODE_VERSION=24

# ------------------------------- base ----------------------------------------
FROM nginx:${NGINX_VERSION}-alpine AS base

COPY docker/nginx/nginx.conf   /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

WORKDIR /var/www/html

HEALTHCHECK --interval=15s --timeout=3s --start-period=10s \
    CMD wget -qO- http://127.0.0.1/healthz >/dev/null 2>&1 || exit 1

# -------------------------------- dev ----------------------------------------
# public/ arrives through a bind mount from the host.
FROM base AS dev

# ------------------------------ assets ---------------------------------------
FROM node:${NODE_VERSION}-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json* ./
RUN --mount=type=cache,target=/root/.npm \
    if [ -f package-lock.json ]; then npm ci; else npm install; fi
COPY . .
RUN npm run build

# -------------------------------- prod ---------------------------------------
# Static files are baked in, so nginx never reads the PHP container's disk.
FROM base AS prod
COPY --from=assets /app/public ./public
