# Laravel + Docker helper targets.  `make help` lists everything.
SHELL      := /bin/bash
DC         := docker compose
DC_PROD    := docker compose -f compose.prod.yaml
PHP        := $(DC) exec -T php
PHP_TTY    := $(DC) exec php
NODE       := $(DC) exec -T node
export UID := $(shell id -u)
export GID := $(shell id -g)

.DEFAULT_GOAL := help

help: ## Show this help
	@grep -hE '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | sort | \
		awk 'BEGIN{FS=":.*?## "};{printf "  \033[36m%-16s\033[0m %s\n",$$1,$$2}'

init: ## First run: .env + build + install a fresh Laravel 13 app if missing
	@test -f .env || cp .env.docker.example .env
	@$(MAKE) build
	@test -f composer.json || $(MAKE) new
	@$(MAKE) up
	@$(PHP) composer install
	@$(PHP) php artisan key:generate
	@$(PHP) php artisan migrate
	@echo "→ http://localhost:$${APP_PORT:-8050}   vite: http://localhost:$${VITE_PORT:-5173}   mail: http://localhost:8025"

new: ## Scaffold Laravel 13 into this directory (keeps the docker files)
	$(DC) run --rm --no-deps php sh -lc '\
		composer create-project laravel/laravel:^13 /tmp/skel --prefer-dist --no-interaction && \
		cd /tmp/skel && tar cf - . | (cd /var/www/html && tar xkf - 2>/dev/null); \
		rm -rf /tmp/skel'
	@test -f vite.config.js && cp vite.config.js vite.config.js.laravel-default || true
	@cp docker/node/vite.config.example.js vite.config.js
	@echo "vite.config.js installed (original kept as vite.config.js.laravel-default)"

build: ## Build all images
	$(DC) build --pull

up: ## Start the stack
	$(DC) up -d --remove-orphans

down: ## Stop the stack
	$(DC) down --remove-orphans

destroy: ## Stop and delete volumes (database included)
	$(DC) down -v --remove-orphans

restart: down up ## Restart the stack

logs: ## Tail logs (make logs s=php)
	$(DC) logs -f --tail=100 $(s)

ps: ## Show container status
	$(DC) ps

sh: ## Shell inside the php container
	$(PHP_TTY) sh

tinker: ## Laravel REPL
	$(PHP_TTY) php artisan tinker

artisan: ## Run artisan (make artisan c="migrate:fresh --seed")
	$(PHP_TTY) php artisan $(c)

composer: ## Run composer (make composer c="require laravel/horizon")
	$(PHP_TTY) composer $(c)

npm: ## Run npm inside the node container (make npm c="i -D vitest")
	$(DC) exec node npm $(c)

migrate: ## Run migrations
	$(PHP) php artisan migrate

fresh: ## Drop everything and re-migrate with seeds
	$(PHP) php artisan migrate:fresh --seed

test: ## Run the test suite
	$(PHP) php artisan test

pint: ## Format with Laravel Pint
	$(PHP) ./vendor/bin/pint

debug: ## Start the stack with Xdebug on
	XDEBUG_MODE=debug $(DC) up -d

optimize: ## Cache config, routes and views
	$(PHP) php artisan optimize

clear: ## Clear every Laravel cache
	$(PHP) php artisan optimize:clear

prod-build: ## Build the production images
	$(DC_PROD) build --pull

prod-up: ## Start the production stack
	$(DC_PROD) up -d

prod-down: ## Stop the production stack
	$(DC_PROD) down

.PHONY: help init new build up down destroy restart logs ps sh tinker artisan \
        composer npm migrate fresh test pint debug optimize clear \
        prod-build prod-up prod-down
