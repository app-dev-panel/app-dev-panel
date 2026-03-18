# ============================================================================
# ADP — Application Development Panel
# Top-level Makefile for running all tests, code quality checks, and CI tasks
# ============================================================================

.PHONY: help test test-php test-frontend test-frontend-e2e test-ci \
        mago mago-format mago-lint mago-analyze mago-fix \
        mago-playgrounds mago-playground-yiisoft mago-playground-symfony mago-playground-yii2 \
        mago-playgrounds-fix mago-playground-yiisoft-fix mago-playground-symfony-fix mago-playground-yii2-fix \
        check check-ci fix \
        install install-php install-frontend install-playgrounds \
        scenarios scenarios-yiisoft scenarios-symfony scenarios-yii2

# --- Port allocation ---
# Frontend dev server
FRONTEND_PORT ?= 8100
# Playground adapter servers (different ports to avoid conflicts)
YIISOFT_PORT  ?= 8101
SYMFONY_PORT  ?= 8102
YII2_PORT     ?= 8103

# --- Binaries ---
# Use vendor/bin/mago locally (absolute path); CI installs mago globally via setup-mago action
MAGO          ?= $(shell [ -x $(CURDIR)/vendor/bin/mago ] && echo $(CURDIR)/vendor/bin/mago || echo mago)

# --- Paths ---
ROOT_DIR      := $(shell pwd)
FRONTEND_DIR  := $(ROOT_DIR)/libs/frontend
PLAYGROUND_DIR := $(ROOT_DIR)/playground

# --- Colors ---
GREEN  := \033[0;32m
YELLOW := \033[0;33m
RED    := \033[0;31m
CYAN   := \033[0;36m
RESET  := \033[0m

# ============================================================================
# Help
# ============================================================================

help: ## Show this help
	@echo ""
	@echo "$(CYAN)ADP Makefile$(RESET)"
	@echo ""
	@echo "$(YELLOW)Testing:$(RESET)"
	@echo "  make test                  Run ALL tests in parallel (PHP unit + frontend unit)"
	@echo "  make test-php              Run PHP unit tests (PHPUnit)"
	@echo "  make test-frontend         Run frontend unit tests (Vitest)"
	@echo "  make test-frontend-e2e     Run frontend browser tests (Vitest + Playwright)"
	@echo "  make test-ci               Run all tests for CI (parallel, GitHub Actions)"
	@echo ""
	@echo "$(YELLOW)Code Quality — Core:$(RESET)"
	@echo "  make mago                  Run Mago checks on core libs (format + lint + analyze)"
	@echo "  make mago-format           Check core code formatting"
	@echo "  make mago-lint             Run core linter"
	@echo "  make mago-analyze          Run core static analyzer"
	@echo "  make mago-fix              Fix core code formatting, then lint + analyze"
	@echo ""
	@echo "$(YELLOW)Code Quality — Playgrounds:$(RESET)"
	@echo "  make mago-playgrounds      Run Mago checks on all playgrounds (parallel)"
	@echo "  make mago-playgrounds-fix  Fix formatting in all playgrounds (parallel)"
	@echo "  make mago-playground-yiisoft   Mago checks for Yiisoft playground"
	@echo "  make mago-playground-symfony   Mago checks for Symfony playground"
	@echo "  make mago-playground-yii2      Mago checks for Yii2 playground"
	@echo ""
	@echo "$(YELLOW)Combined:$(RESET)"
	@echo "  make check                 Run ALL code quality checks (core + playgrounds)"
	@echo "  make check-ci              Run all checks for CI (core + playgrounds + frontend)"
	@echo "  make fix                   Fix all code (core + playgrounds)"
	@echo ""
	@echo "$(YELLOW)Install:$(RESET)"
	@echo "  make install               Install all dependencies"
	@echo "  make install-php           Install PHP dependencies (root)"
	@echo "  make install-frontend      Install frontend dependencies"
	@echo "  make install-playgrounds   Install playground dependencies"
	@echo ""
	@echo "$(YELLOW)Testing Scenarios:$(RESET)"
	@echo "  make scenarios             Run test scenarios against all playgrounds"
	@echo "  make scenarios-yiisoft     Run scenarios against Yiisoft playground"
	@echo "  make scenarios-symfony     Run scenarios against Symfony playground"
	@echo "  make scenarios-yii2        Run scenarios against Yii2 playground"
	@echo ""
	@echo "$(YELLOW)Ports:$(RESET)"
	@echo "  Frontend:  $(FRONTEND_PORT)"
	@echo "  Yiisoft:   $(YIISOFT_PORT)"
	@echo "  Symfony:   $(SYMFONY_PORT)"
	@echo "  Yii2:      $(YII2_PORT)"
	@echo ""

.DEFAULT_GOAL := help

# ============================================================================
# Install
# ============================================================================

install: install-php install-frontend install-playgrounds ## Install all deps

install-php: ## Install PHP deps (root)
	@echo "$(CYAN)Installing PHP dependencies (root)...$(RESET)"
	composer install --prefer-dist --no-progress --no-interaction

install-frontend: ## Install frontend deps
	@echo "$(CYAN)Installing frontend dependencies...$(RESET)"
	cd $(FRONTEND_DIR) && npm install

install-playgrounds: ## Install playground deps
	@echo "$(CYAN)Installing playground dependencies...$(RESET)"
	cd $(PLAYGROUND_DIR)/yiisoft-app && composer install --prefer-dist --no-progress --no-interaction
	cd $(PLAYGROUND_DIR)/symfony-basic-app && composer install --prefer-dist --no-progress --no-interaction
	cd $(PLAYGROUND_DIR)/yii2-basic-app && composer install --prefer-dist --no-progress --no-interaction

# ============================================================================
# Tests
# ============================================================================

test-php: ## Run PHP unit tests (PHPUnit)
	@echo "$(CYAN)Running PHP unit tests...$(RESET)"
	composer test:unit

test-frontend: ## Run frontend unit tests (Vitest)
	@echo "$(CYAN)Running frontend unit tests...$(RESET)"
	cd $(FRONTEND_DIR) && npm test

test-frontend-e2e: ## Run frontend browser tests (Vitest + Playwright)
	@echo "$(CYAN)Running frontend browser tests...$(RESET)"
	cd $(FRONTEND_DIR) && npm run test:e2e

test: ## Run ALL tests in parallel (PHP unit + frontend unit)
	@echo "$(CYAN)Running all tests in parallel...$(RESET)"
	@$(MAKE) -j2 --output-sync=target test-php test-frontend
	@echo ""
	@echo "$(GREEN)All tests passed!$(RESET)"

test-ci: ## Run all tests for CI (parallel, non-interactive)
	@echo "$(CYAN)Running all CI tests in parallel...$(RESET)"
	@$(MAKE) -j2 --output-sync=target test-php test-frontend
	@echo ""
	@echo "$(GREEN)All CI tests passed!$(RESET)"

# ============================================================================
# Code Quality — Core (libs/)
# ============================================================================

mago-format: ## Check core code formatting (Mago)
	@echo "$(CYAN)[Core] Checking code formatting...$(RESET)"
	$(MAGO) fmt --check

mago-lint: ## Run core linter (Mago)
	@echo "$(CYAN)[Core] Running linter...$(RESET)"
	$(MAGO) lint

mago-analyze: ## Run core static analyzer (Mago)
	@echo "$(CYAN)[Core] Running static analyzer...$(RESET)"
	$(MAGO) analyze

mago: mago-format mago-lint mago-analyze ## Run all Mago checks on core
	@echo "$(GREEN)[Core] All Mago checks passed!$(RESET)"

mago-fix: ## Fix core formatting, then lint + analyze
	@echo "$(CYAN)[Core] Fixing code formatting...$(RESET)"
	$(MAGO) fmt
	@$(MAKE) mago-lint mago-analyze

# ============================================================================
# Code Quality — Playgrounds
# ============================================================================

mago-playground-yiisoft: ## Mago checks for Yiisoft playground
	@echo "$(CYAN)[Playground: Yiisoft] Running Mago checks...$(RESET)"
	cd $(PLAYGROUND_DIR)/yiisoft-app && $(MAGO) fmt --check
	cd $(PLAYGROUND_DIR)/yiisoft-app && $(MAGO) lint
	cd $(PLAYGROUND_DIR)/yiisoft-app && $(MAGO) analyze

mago-playground-symfony: ## Mago checks for Symfony playground
	@echo "$(CYAN)[Playground: Symfony] Running Mago checks...$(RESET)"
	cd $(PLAYGROUND_DIR)/symfony-basic-app && $(MAGO) fmt --check
	cd $(PLAYGROUND_DIR)/symfony-basic-app && $(MAGO) lint
	cd $(PLAYGROUND_DIR)/symfony-basic-app && $(MAGO) analyze

mago-playground-yii2: ## Mago checks for Yii2 playground
	@echo "$(CYAN)[Playground: Yii2] Running Mago checks...$(RESET)"
	cd $(PLAYGROUND_DIR)/yii2-basic-app && $(MAGO) fmt --check
	cd $(PLAYGROUND_DIR)/yii2-basic-app && $(MAGO) lint
	cd $(PLAYGROUND_DIR)/yii2-basic-app && $(MAGO) analyze

mago-playgrounds: ## Run Mago checks on all playgrounds (parallel)
	@echo "$(CYAN)Running Mago checks on all playgrounds...$(RESET)"
	@$(MAKE) -j3 --output-sync=target mago-playground-yiisoft mago-playground-symfony mago-playground-yii2
	@echo "$(GREEN)All playground Mago checks passed!$(RESET)"

mago-playground-yiisoft-fix: ## Fix Yiisoft playground formatting
	@echo "$(CYAN)[Playground: Yiisoft] Fixing formatting...$(RESET)"
	cd $(PLAYGROUND_DIR)/yiisoft-app && $(MAGO) fmt
	cd $(PLAYGROUND_DIR)/yiisoft-app && $(MAGO) lint
	cd $(PLAYGROUND_DIR)/yiisoft-app && $(MAGO) analyze

mago-playground-symfony-fix: ## Fix Symfony playground formatting
	@echo "$(CYAN)[Playground: Symfony] Fixing formatting...$(RESET)"
	cd $(PLAYGROUND_DIR)/symfony-basic-app && $(MAGO) fmt
	cd $(PLAYGROUND_DIR)/symfony-basic-app && $(MAGO) lint
	cd $(PLAYGROUND_DIR)/symfony-basic-app && $(MAGO) analyze

mago-playground-yii2-fix: ## Fix Yii2 playground formatting
	@echo "$(CYAN)[Playground: Yii2] Fixing formatting...$(RESET)"
	cd $(PLAYGROUND_DIR)/yii2-basic-app && $(MAGO) fmt
	cd $(PLAYGROUND_DIR)/yii2-basic-app && $(MAGO) lint
	cd $(PLAYGROUND_DIR)/yii2-basic-app && $(MAGO) analyze

mago-playgrounds-fix: ## Fix formatting in all playgrounds (parallel)
	@echo "$(CYAN)Fixing formatting in all playgrounds...$(RESET)"
	@$(MAKE) -j3 --output-sync=target mago-playground-yiisoft-fix mago-playground-symfony-fix mago-playground-yii2-fix
	@echo "$(GREEN)All playground formatting fixed!$(RESET)"

# ============================================================================
# Code Quality — Frontend
# ============================================================================

frontend-check: ## Run frontend code quality checks (Prettier + ESLint)
	@echo "$(CYAN)[Frontend] Running code quality checks...$(RESET)"
	cd $(FRONTEND_DIR) && npm run check

frontend-fix: ## Fix frontend code quality issues
	@echo "$(CYAN)[Frontend] Fixing code quality issues...$(RESET)"
	cd $(FRONTEND_DIR) && npm run format && npm run lint:fix

# ============================================================================
# Combined
# ============================================================================

check: ## Run ALL code quality checks (core + playgrounds + frontend)
	@echo "$(CYAN)Running all code quality checks...$(RESET)"
	@$(MAKE) -j3 --output-sync=target mago mago-playgrounds frontend-check
	@echo ""
	@echo "$(GREEN)All code quality checks passed!$(RESET)"

check-ci: ## Run all checks for CI (core + playgrounds + frontend)
	@echo "$(CYAN)Running all CI checks...$(RESET)"
	@$(MAKE) -j3 --output-sync=target mago mago-playgrounds frontend-check
	@echo ""
	@echo "$(GREEN)All CI checks passed!$(RESET)"

fix: ## Fix all code (core + playgrounds + frontend)
	@echo "$(CYAN)Fixing all code...$(RESET)"
	@$(MAKE) mago-fix
	@$(MAKE) -j3 --output-sync=target mago-playgrounds-fix
	@$(MAKE) frontend-fix
	@echo ""
	@echo "$(GREEN)All code fixed!$(RESET)"

# ============================================================================
# Testing Scenarios
# ============================================================================

scenarios-yiisoft: ## Run test scenarios against Yiisoft playground
	@echo "$(CYAN)[Scenarios: Yiisoft] Running test scenarios on port $(YIISOFT_PORT)...$(RESET)"
	php vendor/bin/adp debug:scenarios http://127.0.0.1:$(YIISOFT_PORT)

scenarios-symfony: ## Run test scenarios against Symfony playground
	@echo "$(CYAN)[Scenarios: Symfony] Running test scenarios on port $(SYMFONY_PORT)...$(RESET)"
	php vendor/bin/adp debug:scenarios http://127.0.0.1:$(SYMFONY_PORT)

scenarios-yii2: ## Run test scenarios against Yii2 playground
	@echo "$(CYAN)[Scenarios: Yii2] Running test scenarios on port $(YII2_PORT)...$(RESET)"
	php vendor/bin/adp debug:scenarios http://127.0.0.1:$(YII2_PORT)

scenarios: ## Run test scenarios against all playgrounds (requires running servers)
	@echo "$(CYAN)Running test scenarios against all playgrounds...$(RESET)"
	@$(MAKE) -j3 --output-sync=target scenarios-yiisoft scenarios-symfony scenarios-yii2
	@echo "$(GREEN)All test scenarios passed!$(RESET)"

# ============================================================================
# Full pipeline
# ============================================================================

all: check test ## Run everything: checks + tests
	@echo ""
	@echo "$(GREEN)========================================$(RESET)"
	@echo "$(GREEN)  All checks and tests passed!$(RESET)"
	@echo "$(GREEN)========================================$(RESET)"

ci: check-ci test-ci ## CI pipeline: all checks + all tests
	@echo ""
	@echo "$(GREEN)========================================$(RESET)"
	@echo "$(GREEN)  CI pipeline passed!$(RESET)"
	@echo "$(GREEN)========================================$(RESET)"
