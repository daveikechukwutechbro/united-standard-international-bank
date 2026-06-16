.PHONY: help setup dev build test lint fix analyse serve clean

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

setup: ## Initial project setup (composer + npm + env + migrate)
	composer install
	npm install
	cp -n .env.example .env || true
	php artisan key:generate --force
	php artisan migrate --seed
	npm run build

dev: ## Start development servers (PHP + Vite)
	php artisan serve & npm run dev

build: ## Build production assets
	npm run build

test: ## Run all tests in parallel
	XDEBUG_MODE=off vendor/bin/pest --parallel --stop-on-failure

test-unit: ## Run unit tests only
	XDEBUG_MODE=off vendor/bin/pest --parallel tests/Unit

test-feature: ## Run feature tests only
	XDEBUG_MODE=off vendor/bin/pest --parallel tests/Feature

lint: ## Run all code quality checks
	./vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php --dry-run --diff
	XDEBUG_MODE=off vendor/bin/phpstan analyse --memory-limit=2G

fix: ## Auto-fix code style
	./vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php

analyse: ## Run static analysis
	XDEBUG_MODE=off vendor/bin/phpstan analyse --memory-limit=2G

serve: ## Start the development server
	php artisan serve

swagger: ## Generate Swagger/OpenAPI documentation
	php artisan l5-swagger:generate

clean: ## Clear all caches
	php artisan cache:clear
	php artisan config:clear
	php artisan route:clear
	php artisan view:clear
