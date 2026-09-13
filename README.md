# Laravel Docker Template

Laravel 13 · PHP 8.5 · nginx · Vite 8 · Redis · Mailpit.
The database is **not** part of this stack: every project joins the shared
MySQL container's network instead.

## Quick start

```bash
cp .env.docker.example .env      # then set DB_DATABASE / DB_PASSWORD
make init                        # build, scaffold Laravel if missing, migrate
```

| what | where |
|---|---|
| app | http://localhost:8050 |
| vite dev server | http://localhost:5173 |
| mailpit | http://localhost:8025 |
| redis (host) | localhost:63790 |

`make help` lists every target.

## Reusing this in another project

Copy `docker/`, `compose.yaml`, `compose.prod.yaml`, `Makefile`,
`.dockerignore` and `.env.docker.example` into the project, set
`COMPOSE_PROJECT_NAME`, `APP_PORT` and `VITE_PORT` to values that do not
collide with your other stacks, then `make build && make up`.

## Layout

```
docker/php/     Dockerfile (base → dev / vendor → prod), php.ini, fpm pool, entrypoint
docker/nginx/   Dockerfile (dev / prod), nginx.conf, Laravel vhost
docker/node/    dev-only Vite image + the container-aware vite.config.js
compose.yaml        development
compose.prod.yaml   production
```

## Database

`DB_HOST=mysql` resolves over the external network named by `DB_NETWORK`
(default `databases_databases-network`). Create the schema once:

```bash
docker exec mysql mysql -uroot -p -e "CREATE DATABASE \`myapp\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

A project that needs its own throwaway database instead can start the bundled
MariaDB and point `DB_HOST` at it:

```bash
docker compose --profile local-db up -d
```

## Vite

`vite.config.js` binds the server to `0.0.0.0` inside the container while the
HMR client keeps dialing `localhost:5173`, with `strictPort` so the port never
drifts to 5174. The Laravel default config is kept as
`vite.config.js.laravel-default`.

## Xdebug

Off by default, no performance cost. Turn it on for a session:

```bash
make debug            # or: XDEBUG_MODE=debug docker compose up -d
```

It connects back to `host.docker.internal:9003`, IDE key `PHPSTORM`.

## Production

```bash
docker compose -f compose.prod.yaml up -d --build
```

The image is self-contained: composer runs with `--no-dev`, the autoloader is
class-map authoritative, `public/build` is compiled by a Node stage, OPcache
runs with `validate_timestamps=0`, and the entrypoint caches config, routes and
views, then migrates with `--isolated`. nginx serves the built assets from its
own image, so it never reads the PHP container's filesystem.

## Notes

* Containers run as uid/gid 1000, so bind-mounted files stay yours.
* Every log goes to stdout/stderr — `make logs s=php`.
* `queue` and `scheduler` reuse the PHP image with a different command.
