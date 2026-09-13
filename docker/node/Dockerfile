# syntax=docker/dockerfile:1.7
# Dev-only image: runs the Vite dev server against the bind-mounted source.
ARG NODE_VERSION=24

FROM node:${NODE_VERSION}-alpine

ARG UID=1000
ARG GID=1000

RUN apk add --no-cache tini \
    && (deluser --remove-home node 2>/dev/null || true) \
    && addgroup -g ${GID} app \
    && adduser -u ${UID} -G app -s /bin/sh -D app

WORKDIR /var/www/html
USER app

EXPOSE 5173
ENTRYPOINT ["/sbin/tini", "--"]
CMD ["sh", "-c", "[ -d node_modules ] || { [ -f package-lock.json ] && npm ci || npm install; }; npm run dev"]
