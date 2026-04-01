# ============================================================================
# ADP — Application Development Panel
# Top-level Makefile for running all tests, code quality checks, and CI tasks
# ============================================================================

.PHONY: help build-panel install-panel build-install-panel test test-php test-frontend test-frontend-e2e test-ci \
        mago mago-format mago-lint mago-analyze mago-fix \
        mago-playgrounds mago-playground-yii3 mago-playground-symfony mago-playground-yii2 mago-playground-laravel \
        mago-playgrounds-fix mago-playground-yii3-fix mago-playground-symfony-fix mago-playground-yii2-fix mago-playground-laravel-fix \
        check check-ci fix \
        install install-php install-frontend install-playgrounds \
        serve-yii3 serve-symfony serve-yii2 serve-laravel serve \
        fixtures fixtures-yii3 fixtures-symfony fixtures-yii2 fixtures-laravel \
        test-fixtures test-fixtures-yii3 test-fixtures-symfony test-fixtures-yii2 test-fixtures-laravel \
        test-scenario test-scenario-yii3 test-scenario-symfony test-scenario-yii2 test-scenario-laravel \
        test-playground test-playground-yii3 test-playground-symfony test-playground-laravel \
        test-mcp test-mcp-yii3 test-mcp-symfony test-mcp-yii2 test-mcp-laravel

# --- Port allocation ---
# Frontend dev server
FRONTEND_PORT ?= 8100
# Playground adapter servers (different ports to avoid conflicts)
YII3_PORT  ?= 8101
SYMFONY_PORT  ?= 8102
YII2_PORT     ?= 8103
LARAVEL_PORT  ?= 8104

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
	@echo "  make test-frontend-e2e     Run frontend browser tests (Vitest + WebDriverIO + ChromeDriver)"
	@echo "  make test-ci               Run all tests for CI (parallel, GitHub Actions)"
	@echo ""
	@echo "$(YELLOW)Build:$(RESET)"
	@echo "  make build-panel           Build panel + toolbar, copy to all adapter assets"
	@echo "  make install-panel         Publish built assets to playground apps"
	@echo "  make build-install-panel   Build + publish in one step"
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
	@echo "  make mago-playground-yii3   Mago checks for Yii 3 playground"
	@echo "  make mago-playground-symfony   Mago checks for Symfony playground"
	@echo "  make mago-playground-yii2      Mago checks for Yii2 playground"
	@echo "  make mago-playground-laravel   Mago checks for Laravel playground"
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
	@echo "$(YELLOW)Playground Servers:$(RESET)"
	@echo "  make serve                 Start all playground servers in background"
	@echo "  make serve-yii3         Start Yii 3 server (port $(YII3_PORT))"
	@echo "  make serve-symfony         Start Symfony server (port $(SYMFONY_PORT))"
	@echo "  make serve-yii2            Start Yii2 server (port $(YII2_PORT))"
	@echo "  make serve-laravel         Start Laravel server (port $(LARAVEL_PORT))"
	@echo ""
	@echo "$(YELLOW)Testing Fixtures:$(RESET)"
	@echo "  make fixtures             Run test fixtures against all playgrounds"
	@echo "  make fixtures-yii3     Run fixtures against Yii 3 playground"
	@echo "  make fixtures-symfony     Run fixtures against Symfony playground"
	@echo "  make fixtures-yii2        Run fixtures against Yii2 playground"
	@echo "  make fixtures-laravel     Run fixtures against Laravel playground"
	@echo ""
	@echo "$(YELLOW)E2E Fixture Tests (PHPUnit):$(RESET)"
	@echo "  make test-fixtures        Run PHPUnit E2E fixtures against all playgrounds"
	@echo "  make test-fixtures-yii3   PHPUnit E2E against Yii 3 playground"
	@echo "  make test-fixtures-symfony   PHPUnit E2E against Symfony playground"
	@echo "  make test-fixtures-yii2      PHPUnit E2E against Yii2 playground"
	@echo "  make test-scenario        Full scenario: clear, fire all, verify pipeline"
	@echo "  make test-scenario-yii3   Scenario against Yii 3 playground"
	@echo "  make test-scenario-symfony   Scenario against Symfony playground"
	@echo "  make test-scenario-yii2      Scenario against Yii2 playground"
	@echo "  make test-mcp             MCP API E2E tests against all playgrounds"
	@echo "  make test-mcp-yii3     MCP API E2E against Yii 3 playground"
	@echo "  make test-mcp-symfony     MCP API E2E against Symfony playground"
	@echo "  make test-mcp-yii2        MCP API E2E against Yii2 playground"
	@echo "  make test-mcp-laravel     MCP API E2E against Laravel playground"
	@echo ""
	@echo "$(YELLOW)Ports:$(RESET)"
	@echo "  Frontend:  $(FRONTEND_PORT)"
	@echo "  Yii3:   $(YII3_PORT)"
	@echo "  Symfony:   $(SYMFONY_PORT)"
	@echo "  Yii2:      $(YII2_PORT)"
	@echo "  Laravel:   $(LARAVEL_PORT)"
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
	cd $(PLAYGROUND_DIR)/yii3-app && composer install --prefer-dist --no-progress --no-interaction
	cd $(PLAYGROUND_DIR)/symfony-app && composer install --prefer-dist --no-progress --no-interaction
	cd $(PLAYGROUND_DIR)/yii2-basic-app && composer install --prefer-dist --no-progress --no-interaction
	cd $(PLAYGROUND_DIR)/laravel-app && composer install --prefer-dist --no-progress --no-interaction

# ============================================================================
# Build
# ============================================================================

PANEL_DIST    := $(FRONTEND_DIR)/packages/panel/dist
TOOLBAR_DIST  := $(FRONTEND_DIR)/packages/toolbar/dist
SYMFONY_ASSETS := $(ROOT_DIR)/libs/Adapter/Symfony/Resources/public
LARAVEL_ASSETS := $(ROOT_DIR)/libs/Adapter/Laravel/resources/dist
YII2_ASSETS    := $(ROOT_DIR)/libs/Adapter/Yii2/resources/dist
YII3_ASSETS := $(ROOT_DIR)/libs/Adapter/Yii3/resources/dist

build-panel: ## Build panel + toolbar and copy to all adapter asset directories
	@echo "$(CYAN)Building frontend panel...$(RESET)"
	cd $(FRONTEND_DIR) && npx lerna run build --scope=@app-dev-panel/panel --scope=@app-dev-panel/toolbar
	@echo "$(CYAN)Copying assets to adapters...$(RESET)"
	@for dir in $(SYMFONY_ASSETS) $(LARAVEL_ASSETS) $(YII2_ASSETS) $(YII3_ASSETS); do \
		mkdir -p $$dir; \
		find $$dir -mindepth 1 -maxdepth 1 -not -name '.gitignore' -not -name '.gitkeep' -exec rm -rf {} + 2>/dev/null; \
		cp $(PANEL_DIST)/bundle.js $(PANEL_DIST)/bundle*.css $$dir/; \
		if [ -d "$(PANEL_DIST)/assets" ]; then cp -r $(PANEL_DIST)/assets $$dir/assets; fi; \
	done
	@echo "$(GREEN)Done. Run 'make install-panel' to publish assets to playgrounds.$(RESET)"

install-panel: ## Publish built panel assets into playground applications
	@echo "$(CYAN)Publishing panel assets to playgrounds...$(RESET)"
	cd $(PLAYGROUND_DIR)/symfony-app && rm -rf public/bundles/appdevpanel && php bin/console assets:install public --symlink
	@echo "$(GREEN)Done. Panel available at /debug on each playground.$(RESET)"

build-install-panel: build-panel install-panel ## Build panel + publish to all playgrounds

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

mago-playground-yii3: ## Mago checks for Yii 3 playground
	@echo "$(CYAN)[Playground: Yii3] Running Mago checks...$(RESET)"
	cd $(PLAYGROUND_DIR)/yii3-app && $(MAGO) fmt --check
	cd $(PLAYGROUND_DIR)/yii3-app && $(MAGO) lint
	cd $(PLAYGROUND_DIR)/yii3-app && $(MAGO) analyze

mago-playground-symfony: ## Mago checks for Symfony playground
	@echo "$(CYAN)[Playground: Symfony] Running Mago checks...$(RESET)"
	cd $(PLAYGROUND_DIR)/symfony-app && $(MAGO) fmt --check
	cd $(PLAYGROUND_DIR)/symfony-app && $(MAGO) lint
	cd $(PLAYGROUND_DIR)/symfony-app && $(MAGO) analyze

mago-playground-yii2: ## Mago checks for Yii2 playground
	@echo "$(CYAN)[Playground: Yii2] Running Mago checks...$(RESET)"
	cd $(PLAYGROUND_DIR)/yii2-basic-app && $(MAGO) fmt --check
	cd $(PLAYGROUND_DIR)/yii2-basic-app && $(MAGO) lint
	cd $(PLAYGROUND_DIR)/yii2-basic-app && $(MAGO) analyze

mago-playground-laravel: ## Mago checks for Laravel playground
	@echo "$(CYAN)[Playground: Laravel] Running Mago checks...$(RESET)"
	cd $(PLAYGROUND_DIR)/laravel-app && $(MAGO) fmt --check
	cd $(PLAYGROUND_DIR)/laravel-app && $(MAGO) lint
	cd $(PLAYGROUND_DIR)/laravel-app && $(MAGO) analyze

mago-playgrounds: ## Run Mago checks on all playgrounds (parallel)
	@echo "$(CYAN)Running Mago checks on all playgrounds...$(RESET)"
	@$(MAKE) -j4 --output-sync=target mago-playground-yii3 mago-playground-symfony mago-playground-yii2 mago-playground-laravel
	@echo "$(GREEN)All playground Mago checks passed!$(RESET)"

mago-playground-yii3-fix: ## Fix Yii 3 playground formatting
	@echo "$(CYAN)[Playground: Yii3] Fixing formatting...$(RESET)"
	cd $(PLAYGROUND_DIR)/yii3-app && $(MAGO) fmt
	cd $(PLAYGROUND_DIR)/yii3-app && $(MAGO) lint
	cd $(PLAYGROUND_DIR)/yii3-app && $(MAGO) analyze

mago-playground-symfony-fix: ## Fix Symfony playground formatting
	@echo "$(CYAN)[Playground: Symfony] Fixing formatting...$(RESET)"
	cd $(PLAYGROUND_DIR)/symfony-app && $(MAGO) fmt
	cd $(PLAYGROUND_DIR)/symfony-app && $(MAGO) lint
	cd $(PLAYGROUND_DIR)/symfony-app && $(MAGO) analyze

mago-playground-yii2-fix: ## Fix Yii2 playground formatting
	@echo "$(CYAN)[Playground: Yii2] Fixing formatting...$(RESET)"
	cd $(PLAYGROUND_DIR)/yii2-basic-app && $(MAGO) fmt
	cd $(PLAYGROUND_DIR)/yii2-basic-app && $(MAGO) lint
	cd $(PLAYGROUND_DIR)/yii2-basic-app && $(MAGO) analyze

mago-playground-laravel-fix: ## Fix Laravel playground formatting
	@echo "$(CYAN)[Playground: Laravel] Fixing formatting...$(RESET)"
	cd $(PLAYGROUND_DIR)/laravel-app && $(MAGO) fmt
	cd $(PLAYGROUND_DIR)/laravel-app && $(MAGO) lint
	cd $(PLAYGROUND_DIR)/laravel-app && $(MAGO) analyze

mago-playgrounds-fix: ## Fix formatting in all playgrounds (parallel)
	@echo "$(CYAN)Fixing formatting in all playgrounds...$(RESET)"
	@$(MAKE) -j4 --output-sync=target mago-playground-yii3-fix mago-playground-symfony-fix mago-playground-yii2-fix mago-playground-laravel-fix
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
# Playground Servers
# ============================================================================

serve-yii3: ## Start Yii 3 playground server (port $(YII3_PORT))
	@echo "$(CYAN)[Playground: Yii3] Starting server on port $(YII3_PORT)...$(RESET)"
	cd $(PLAYGROUND_DIR)/yii3-app && php ./yii serve --port=$(YII3_PORT)

serve-symfony: ## Start Symfony playground server (port $(SYMFONY_PORT))
	@echo "$(CYAN)[Playground: Symfony] Starting server on port $(SYMFONY_PORT)...$(RESET)"
	cd $(PLAYGROUND_DIR)/symfony-app && bash ../../bin/serve.sh $(SYMFONY_PORT)

serve-yii2: ## Start Yii2 playground server (port $(YII2_PORT))
	@echo "$(CYAN)[Playground: Yii2] Starting server on port $(YII2_PORT)...$(RESET)"
	cd $(PLAYGROUND_DIR)/yii2-basic-app && bash ../../bin/serve.sh $(YII2_PORT)

serve-laravel: ## Start Laravel playground server (port $(LARAVEL_PORT))
	@echo "$(CYAN)[Playground: Laravel] Starting server on port $(LARAVEL_PORT)...$(RESET)"
	cd $(PLAYGROUND_DIR)/laravel-app && bash ../../bin/serve.sh $(LARAVEL_PORT)

serve: ## Start all playground servers in background
	@echo "$(CYAN)Starting all playground servers...$(RESET)"
	@$(MAKE) serve-yii3 &
	@$(MAKE) serve-symfony &
	@$(MAKE) serve-yii2 &
	@$(MAKE) serve-laravel &
	@sleep 1
	@echo ""
	@echo "$(GREEN)Playground servers started:$(RESET)"
	@echo "  Yii3:  http://127.0.0.1:$(YII3_PORT)"
	@echo "  Symfony:  http://127.0.0.1:$(SYMFONY_PORT)"
	@echo "  Yii2:     http://127.0.0.1:$(YII2_PORT)"
	@echo "  Laravel:  http://127.0.0.1:$(LARAVEL_PORT)"
	@echo ""
	@echo "$(YELLOW)Press Ctrl+C to stop all servers$(RESET)"
	@wait

# ============================================================================
# Testing Fixtures
# ============================================================================

fixtures-yii3: ## Run test fixtures against Yii 3 playground
	@echo "$(CYAN)[Scenarios: Yii3] Running test fixtures on port $(YII3_PORT)...$(RESET)"
	php vendor/bin/adp debug:fixtures http://127.0.0.1:$(YII3_PORT)

fixtures-symfony: ## Run test fixtures against Symfony playground
	@echo "$(CYAN)[Scenarios: Symfony] Running test fixtures on port $(SYMFONY_PORT)...$(RESET)"
	php vendor/bin/adp debug:fixtures http://127.0.0.1:$(SYMFONY_PORT)

fixtures-yii2: ## Run test fixtures against Yii2 playground
	@echo "$(CYAN)[Scenarios: Yii2] Running test fixtures on port $(YII2_PORT)...$(RESET)"
	php vendor/bin/adp debug:fixtures http://127.0.0.1:$(YII2_PORT)

fixtures-laravel: ## Run test fixtures against Laravel playground
	@echo "$(CYAN)[Scenarios: Laravel] Running test fixtures on port $(LARAVEL_PORT)...$(RESET)"
	php vendor/bin/adp debug:fixtures http://127.0.0.1:$(LARAVEL_PORT)

fixtures: ## Run test fixtures against all playgrounds (requires running servers)
	@echo "$(CYAN)Running test fixtures against all playgrounds...$(RESET)"
	@$(MAKE) -j4 --output-sync=target fixtures-yii3 fixtures-symfony fixtures-yii2 fixtures-laravel
	@echo "$(GREEN)All test fixtures passed!$(RESET)"

test-fixtures-yii3: ## Run PHPUnit E2E fixtures against Yii 3 playground
	@echo "$(CYAN)[E2E Fixtures: Yii3] Running PHPUnit E2E tests on port $(YII3_PORT)...$(RESET)"
	PLAYGROUND_URL=http://127.0.0.1:$(YII3_PORT) php vendor/bin/phpunit --testsuite Fixtures --testdox

test-fixtures-symfony: ## Run PHPUnit E2E fixtures against Symfony playground
	@echo "$(CYAN)[E2E Fixtures: Symfony] Running PHPUnit E2E tests on port $(SYMFONY_PORT)...$(RESET)"
	PLAYGROUND_URL=http://127.0.0.1:$(SYMFONY_PORT) php vendor/bin/phpunit --testsuite Fixtures --testdox

test-fixtures-yii2: ## Run PHPUnit E2E fixtures against Yii2 playground
	@echo "$(CYAN)[E2E Fixtures: Yii2] Running PHPUnit E2E tests on port $(YII2_PORT)...$(RESET)"
	PLAYGROUND_URL=http://127.0.0.1:$(YII2_PORT) php vendor/bin/phpunit --testsuite Fixtures --testdox

test-fixtures-laravel: ## Run PHPUnit E2E fixtures against Laravel playground
	@echo "$(CYAN)[E2E Fixtures: Laravel] Running PHPUnit E2E tests on port $(LARAVEL_PORT)...$(RESET)"
	PLAYGROUND_URL=http://127.0.0.1:$(LARAVEL_PORT) php vendor/bin/phpunit --testsuite Fixtures --testdox

test-fixtures: ## Run PHPUnit E2E fixtures against all playgrounds (requires running servers)
	@echo "$(CYAN)Running PHPUnit E2E fixtures against all playgrounds...$(RESET)"
	@$(MAKE) -j4 --output-sync=target test-fixtures-yii3 test-fixtures-symfony test-fixtures-yii2 test-fixtures-laravel
	@echo "$(GREEN)All E2E fixture tests passed!$(RESET)"

test-scenario-yii3: ## Run full scenario test against Yii 3 playground
	@echo "$(CYAN)[Scenario: Yii3] Running full scenario on port $(YII3_PORT)...$(RESET)"
	PLAYGROUND_URL=http://127.0.0.1:$(YII3_PORT) php vendor/bin/phpunit --testsuite Fixtures --group scenario --testdox

test-scenario-symfony: ## Run full scenario test against Symfony playground
	@echo "$(CYAN)[Scenario: Symfony] Running full scenario on port $(SYMFONY_PORT)...$(RESET)"
	PLAYGROUND_URL=http://127.0.0.1:$(SYMFONY_PORT) php vendor/bin/phpunit --testsuite Fixtures --group scenario --testdox

test-scenario-yii2: ## Run full scenario test against Yii2 playground
	@echo "$(CYAN)[Scenario: Yii2] Running full scenario on port $(YII2_PORT)...$(RESET)"
	PLAYGROUND_URL=http://127.0.0.1:$(YII2_PORT) php vendor/bin/phpunit --testsuite Fixtures --group scenario --testdox

test-scenario-laravel: ## Run full scenario test against Laravel playground
	@echo "$(CYAN)[Scenario: Laravel] Running full scenario on port $(LARAVEL_PORT)...$(RESET)"
	PLAYGROUND_URL=http://127.0.0.1:$(LARAVEL_PORT) php vendor/bin/phpunit --testsuite Fixtures --group scenario --testdox

test-scenario: ## Run full scenario test against all playgrounds (requires running servers)
	@echo "$(CYAN)Running full scenario tests against all playgrounds...$(RESET)"
	@$(MAKE) -j4 --output-sync=target test-scenario-yii3 test-scenario-symfony test-scenario-yii2 test-scenario-laravel
	@echo "$(GREEN)All scenario tests passed!$(RESET)"

test-mcp-yii3: ## Run MCP API E2E tests against Yii 3 playground
	@echo "$(CYAN)[MCP E2E: Yii3] Running MCP API tests on port $(YII3_PORT)...$(RESET)"
	PLAYGROUND_URL=http://127.0.0.1:$(YII3_PORT) php vendor/bin/phpunit --testsuite Fixtures --group mcp --testdox

test-mcp-symfony: ## Run MCP API E2E tests against Symfony playground
	@echo "$(CYAN)[MCP E2E: Symfony] Running MCP API tests on port $(SYMFONY_PORT)...$(RESET)"
	PLAYGROUND_URL=http://127.0.0.1:$(SYMFONY_PORT) php vendor/bin/phpunit --testsuite Fixtures --group mcp --testdox

test-mcp-yii2: ## Run MCP API E2E tests against Yii2 playground
	@echo "$(CYAN)[MCP E2E: Yii2] Running MCP API tests on port $(YII2_PORT)...$(RESET)"
	PLAYGROUND_URL=http://127.0.0.1:$(YII2_PORT) php vendor/bin/phpunit --testsuite Fixtures --group mcp --testdox

test-mcp-laravel: ## Run MCP API E2E tests against Laravel playground
	@echo "$(CYAN)[MCP E2E: Laravel] Running MCP API tests on port $(LARAVEL_PORT)...$(RESET)"
	PLAYGROUND_URL=http://127.0.0.1:$(LARAVEL_PORT) php vendor/bin/phpunit --testsuite Fixtures --group mcp --testdox

test-mcp: ## Run MCP API E2E tests against all playgrounds (requires running servers)
	@echo "$(CYAN)Running MCP API E2E tests against all playgrounds...$(RESET)"
	@$(MAKE) -j4 --output-sync=target test-mcp-yii3 test-mcp-symfony test-mcp-yii2 test-mcp-laravel
	@echo "$(GREEN)All MCP API E2E tests passed!$(RESET)"

# ============================================================================
# Playground Integration Tests (starts server, runs tests, stops server)
# ============================================================================

test-playground-yii3: ## Start Yii 3 server, run E2E fixtures + scenario, stop server
	@echo "$(CYAN)[Playground: Yii3] Starting server on port $(YII3_PORT)...$(RESET)"
	@cd $(PLAYGROUND_DIR)/yii3-app && composer serve &>/dev/null & echo $$! > /tmp/adp-yii3.pid
	@sleep 3
	@echo "$(CYAN)[Playground: Yii3] Running E2E fixture tests...$(RESET)"
	@PLAYGROUND_URL=http://127.0.0.1:$(YII3_PORT) php vendor/bin/phpunit --testsuite Fixtures --testdox; \
		EXIT_CODE=$$?; \
		kill $$(cat /tmp/adp-yii3.pid) 2>/dev/null || true; rm -f /tmp/adp-yii3.pid; \
		pkill -f "127.0.0.1:$(YII3_PORT)" 2>/dev/null || true; \
		exit $$EXIT_CODE

test-playground-symfony: ## Start Symfony server, run E2E fixtures + scenario, stop server
	@echo "$(CYAN)[Playground: Symfony] Starting server on port $(SYMFONY_PORT)...$(RESET)"
	@cd $(PLAYGROUND_DIR)/symfony-app && composer serve &>/dev/null & echo $$! > /tmp/adp-symfony.pid
	@sleep 3
	@echo "$(CYAN)[Playground: Symfony] Running E2E fixture tests...$(RESET)"
	@PLAYGROUND_URL=http://127.0.0.1:$(SYMFONY_PORT) php vendor/bin/phpunit --testsuite Fixtures --testdox; \
		EXIT_CODE=$$?; \
		kill $$(cat /tmp/adp-symfony.pid) 2>/dev/null || true; rm -f /tmp/adp-symfony.pid; \
		pkill -f "127.0.0.1:$(SYMFONY_PORT)" 2>/dev/null || true; \
		exit $$EXIT_CODE

test-playground-laravel: ## Start Laravel server, run E2E fixtures + scenario, stop server
	@echo "$(CYAN)[Playground: Laravel] Starting server on port $(LARAVEL_PORT)...$(RESET)"
	@cd $(PLAYGROUND_DIR)/laravel-app && composer serve &>/dev/null & echo $$! > /tmp/adp-laravel.pid
	@sleep 3
	@echo "$(CYAN)[Playground: Laravel] Running E2E fixture tests...$(RESET)"
	@PLAYGROUND_URL=http://127.0.0.1:$(LARAVEL_PORT) php vendor/bin/phpunit --testsuite Fixtures --testdox; \
		EXIT_CODE=$$?; \
		kill $$(cat /tmp/adp-laravel.pid) 2>/dev/null || true; rm -f /tmp/adp-laravel.pid; \
		pkill -f "127.0.0.1:$(LARAVEL_PORT)" 2>/dev/null || true; \
		exit $$EXIT_CODE

test-playground: ## Run playground integration tests (starts servers, runs E2E, stops servers)
	@echo "$(CYAN)Running playground integration tests...$(RESET)"
	@$(MAKE) test-playground-yii3
	@$(MAKE) test-playground-symfony
	@$(MAKE) test-playground-laravel
	@echo "$(GREEN)All playground integration tests passed!$(RESET)"

# ============================================================================
# Full pipeline
# ============================================================================

all: check test test-playground ## Run everything: checks + tests + playground E2E
	@echo ""
	@echo "$(GREEN)========================================$(RESET)"
	@echo "$(GREEN)  All checks and tests passed!$(RESET)"
	@echo "$(GREEN)========================================$(RESET)"

ci: check-ci test-ci ## CI pipeline: all checks + all tests
	@echo ""
	@echo "$(GREEN)========================================$(RESET)"
	@echo "$(GREEN)  CI pipeline passed!$(RESET)"
	@echo "$(GREEN)========================================$(RESET)"
