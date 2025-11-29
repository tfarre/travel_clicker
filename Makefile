.PHONY: help install dev build tests fix stan cs lint clean

# Default target
help:
	@echo "Travel Clicker - Available commands:"
	@echo ""
	@echo "  make install    - Install all dependencies (composer + npm)"
	@echo "  make dev        - Start development servers (Symfony + Vite)"
	@echo "  make build      - Build production assets"
	@echo "  make tests      - Run PHPUnit tests"
	@echo "  make fix        - Run CS-Fixer and PHPStan"
	@echo "  make stan       - Run PHPStan only"
	@echo "  make cs         - Run PHP-CS-Fixer only"
	@echo "  make lint       - Lint all files (PHP + JS)"
	@echo "  make clean      - Clear cache and remove generated files"
	@echo ""

# Install all dependencies
install:
	composer install
	npm install

# Start development environment
dev:
	@echo "Starting Symfony server and Vite dev server..."
	@echo "Run these commands in separate terminals:"
	@echo "  Terminal 1: symfony serve"
	@echo "  Terminal 2: npm run dev"

# Build production assets
build:
	npm run build

# Run PHPUnit tests
tests:
	php bin/phpunit

# Run all code quality tools
fix: cs stan

# Run PHPStan static analysis
stan:
	php vendor/bin/phpstan analyse

# Run PHP-CS-Fixer
cs:
	php vendor/bin/php-cs-fixer fix

# Run PHP-CS-Fixer in dry-run mode
cs-check:
	php vendor/bin/php-cs-fixer fix --dry-run --diff

# Lint all files
lint: cs-check stan

# Clear cache and generated files
clean:
	php bin/console cache:clear
	rm -rf var/cache/*
	rm -rf public/build/*

# Database commands
db-create:
	php bin/console doctrine:database:create --if-not-exists

db-migrate:
	php bin/console doctrine:migrations:migrate --no-interaction

db-diff:
	php bin/console doctrine:migrations:diff

# Generate Symfony container for PHPStan
phpstan-baseline:
	php bin/console cache:warmup
	php vendor/bin/phpstan analyse --generate-baseline
