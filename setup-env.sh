#!/usr/bin/env bash
# =============================================================================
# ADP — Environment Setup Script
# Runs once when a new Claude Code session starts.
# Installs system-level tools and project dependencies so that
# `make all` (checks + tests) can pass immediately.
# =============================================================================

set -euo pipefail

PROJECT_DIR="/home/user/app-dev-panel"

# ----------------------------------------------------------
# Colors
# ----------------------------------------------------------
GREEN='\033[0;32m'
CYAN='\033[0;36m'
YELLOW='\033[0;33m'
RED='\033[0;31m'
RESET='\033[0m'

step() { echo -e "${CYAN}▸ $1${RESET}"; }
ok()   { echo -e "${GREEN}  ✓ $1${RESET}"; }
warn() { echo -e "${YELLOW}  ⚠ $1${RESET}"; }
fail() { echo -e "${RED}  ✗ $1${RESET}"; }

# ----------------------------------------------------------
# 1. PCOV — PHP code coverage driver (build from source)
#    Required for: make test-php with --coverage-text/html
# ----------------------------------------------------------
step "Checking PCOV extension..."

if php -m 2>/dev/null | grep -qi pcov; then
    ok "PCOV already loaded"
else
    step "Building PCOV from source..."

    PCOV_VERSION="1.0.12"
    PCOV_DIR="/tmp/pcov-build"

    # Ensure build tools are available
    if ! command -v phpize &>/dev/null; then
        apt-get update -qq && apt-get install -y -qq php8.4-dev build-essential > /dev/null 2>&1
    fi

    rm -rf "$PCOV_DIR"
    mkdir -p "$PCOV_DIR"
    cd "$PCOV_DIR"

    curl -sSL "https://pecl.php.net/get/pcov-${PCOV_VERSION}.tgz" -o pcov.tgz
    tar xzf pcov.tgz --strip-components=1

    phpize
    ./configure --enable-pcov > /dev/null
    make -j"$(nproc)" -s
    make install -s

    # Enable the extension
    INI_DIR="$(php -i 2>/dev/null | grep 'Scan this dir' | awk -F'=>' '{print $NF}' | xargs)"
    if [ -n "$INI_DIR" ] && [ -d "$INI_DIR" ]; then
        echo "extension=pcov.so" > "${INI_DIR}/20-pcov.ini"
    fi

    cd "$PROJECT_DIR"
    rm -rf "$PCOV_DIR"

    if php -m 2>/dev/null | grep -qi pcov; then
        ok "PCOV installed and loaded"
    else
        fail "PCOV installation failed — coverage reports will not work"
    fi
fi

# ----------------------------------------------------------
# 2. PHP dependencies (Composer)
#    Mago (linter/formatter/analyzer) is included as a
#    Composer dev dependency — no separate install needed.
# ----------------------------------------------------------
step "Installing PHP dependencies (includes Mago)..."

cd "$PROJECT_DIR"
composer install --prefer-dist --no-progress --no-interaction -q
ok "PHP dependencies installed"

# Warm up Mago binary (it self-downloads on first run)
if [ -x "vendor/bin/mago" ]; then
    vendor/bin/mago --version > /dev/null 2>&1
    ok "Mago available ($(vendor/bin/mago --version 2>&1))"
else
    warn "Mago not found in vendor/bin — check composer.json"
fi

# ----------------------------------------------------------
# 3. Frontend dependencies (npm)
# ----------------------------------------------------------
step "Installing frontend dependencies..."

cd "$PROJECT_DIR/libs/frontend"
npm install --prefer-offline --no-audit --no-fund --loglevel=warn
ok "Frontend dependencies installed"

# ----------------------------------------------------------
# 4. Playwright / Chromium (for frontend e2e tests)
#    Required for: make test-frontend-e2e
# ----------------------------------------------------------
step "Checking Playwright Chromium..."

# Find the Playwright chromium executable dynamically
PLAYWRIGHT_CHROME=""
if [ -d /root/.cache/ms-playwright ]; then
    PLAYWRIGHT_CHROME="$(find /root/.cache/ms-playwright -name chrome -path '*/chrome-linux/*' -type f 2>/dev/null | head -1)"
fi

if [ -n "$PLAYWRIGHT_CHROME" ] && [ -x "$PLAYWRIGHT_CHROME" ]; then
    ok "Playwright Chromium already installed at ${PLAYWRIGHT_CHROME}"
else
    step "Installing Playwright Chromium..."
    npx playwright install chromium 2>/dev/null && \
        ok "Playwright Chromium installed" || \
        warn "Playwright install failed — e2e tests may not work"
fi

# ----------------------------------------------------------
# 5. Playground dependencies (Composer)
#    Required for: make mago-playgrounds, make test-fixtures
# ----------------------------------------------------------
step "Installing playground dependencies..."

cd "$PROJECT_DIR"

for playground in yiisoft-app symfony-basic-app yii2-basic-app laravel-app; do
    PDIR="playground/${playground}"
    if [ -d "$PDIR" ]; then
        (cd "$PDIR" && composer install --prefer-dist --no-progress --no-interaction -q)
        ok "${playground}"
    fi
done

# ----------------------------------------------------------
# Summary
# ----------------------------------------------------------
echo ""
echo -e "${GREEN}══════════════════════════════════════════${RESET}"
echo -e "${GREEN}  Environment ready!${RESET}"
echo -e "${GREEN}══════════════════════════════════════════${RESET}"
echo ""
echo -e "  PHP:          $(php -v 2>&1 | head -1 | awk '{print $2}')"
echo -e "  PCOV:         $(php -m 2>/dev/null | grep -qi pcov && echo 'loaded' || echo 'NOT loaded')"
echo -e "  Node:         $(node -v 2>&1)"
echo -e "  npm:          $(npm -v 2>&1)"
echo -e "  Mago:         $("$PROJECT_DIR/vendor/bin/mago" --version 2>/dev/null || echo 'not found')"
echo -e "  Playwright:   $([ -n "${PLAYWRIGHT_CHROME:-}" ] && [ -x "${PLAYWRIGHT_CHROME:-}" ] && echo 'chromium ready' || echo 'not found')"
echo ""
echo -e "  Run ${CYAN}make all${RESET} to verify everything works."
echo ""
