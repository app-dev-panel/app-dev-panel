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
# 2. Chromium + ChromeDriver (for e2e tests)
#    Required for: make test-frontend-e2e, PHPUnit E2E suite
#    WebDriverIO looks for `google-chrome` via locateChrome().
# ----------------------------------------------------------
step "Checking Chromium..."

CHROME_BIN=""
if command -v google-chrome &>/dev/null; then
    CHROME_BIN="$(command -v google-chrome)"
elif command -v chromium &>/dev/null; then
    CHROME_BIN="$(command -v chromium)"
elif command -v chromium-browser &>/dev/null; then
    CHROME_BIN="$(command -v chromium-browser)"
fi

if [ -n "$CHROME_BIN" ]; then
    ok "Chromium found at ${CHROME_BIN} ($($CHROME_BIN --version --no-sandbox 2>&1 | head -1))"
else
    step "Installing Chromium..."
    if apt-get update -qq && apt-get install -y -qq chromium-browser > /dev/null 2>&1; then
        CHROME_BIN="$(command -v chromium-browser 2>/dev/null || command -v chromium 2>/dev/null)"
        ok "Chromium installed"
    else
        warn "Chromium install failed — e2e tests may not work"
    fi
fi

# WebDriverIO's locateChrome() looks for `google-chrome` on Linux.
# Create symlinks so it finds our Chromium binary.
if [ -n "$CHROME_BIN" ]; then
    if [ ! -e /usr/bin/google-chrome ]; then
        ln -sf "$CHROME_BIN" /usr/bin/google-chrome 2>/dev/null || true
    fi
    if [ ! -e /usr/bin/chromium ]; then
        ln -sf "$CHROME_BIN" /usr/bin/chromium 2>/dev/null || true
    fi
fi

step "Checking ChromeDriver..."

# Detect Chromium major version for matching
CHROME_MAJOR=""
if [ -n "$CHROME_BIN" ]; then
    CHROME_VERSION="$("$CHROME_BIN" --version --no-sandbox 2>/dev/null | grep -oP '\d+\.\d+\.\d+\.\d+' | head -1 || echo "")"
    CHROME_MAJOR="$(echo "$CHROME_VERSION" | cut -d. -f1)"
fi

# Check if existing chromedriver version matches Chromium
NEED_CHROMEDRIVER=1
if command -v chromedriver &>/dev/null; then
    DRIVER_VERSION="$(chromedriver --version 2>&1 | grep -oP '\d+' | head -1 || echo "")"
    if [ -n "$CHROME_MAJOR" ] && [ "$DRIVER_VERSION" = "$CHROME_MAJOR" ]; then
        ok "ChromeDriver found, version matches Chromium ${CHROME_MAJOR} ($(chromedriver --version 2>&1 | head -1))"
        NEED_CHROMEDRIVER=0
    else
        warn "ChromeDriver version mismatch (driver: ${DRIVER_VERSION}, chromium: ${CHROME_MAJOR}) — reinstalling"
    fi
fi

if [ "$NEED_CHROMEDRIVER" -eq 1 ] && [ -n "$CHROME_MAJOR" ]; then
    step "Installing ChromeDriver for Chromium ${CHROME_MAJOR}..."
    INSTALLED=0

    # Try Chrome for Testing (CfT) endpoint (Chromium 115+)
    if [ "$CHROME_MAJOR" -ge 115 ] 2>/dev/null; then
        CFT_URL="https://googlechromelabs.github.io/chrome-for-testing/LATEST_RELEASE_${CHROME_MAJOR}"
        CFT_VERSION="$(curl -sSL "$CFT_URL" 2>/dev/null | tr -d '[:space:]')"

        if [ -n "$CFT_VERSION" ]; then
            DRIVER_URL="https://storage.googleapis.com/chrome-for-testing-public/${CFT_VERSION}/linux64/chromedriver-linux64.zip"
            if curl -sSL "$DRIVER_URL" -o /tmp/chromedriver.zip 2>/dev/null; then
                cd /tmp && unzip -qo chromedriver.zip 2>/dev/null && \
                    mv chromedriver-linux64/chromedriver /usr/local/bin/chromedriver && \
                    chmod +x /usr/local/bin/chromedriver && \
                    rm -rf /tmp/chromedriver.zip /tmp/chromedriver-linux64 && \
                    INSTALLED=1
                cd "$PROJECT_DIR"
            fi
        fi
    fi

    # Fallback: apt
    if [ "$INSTALLED" -eq 0 ]; then
        apt-get update -qq && apt-get install -y -qq chromium-chromedriver > /dev/null 2>&1 && INSTALLED=1
    fi

    if [ "$INSTALLED" -eq 1 ] && command -v chromedriver &>/dev/null; then
        ok "ChromeDriver installed ($(chromedriver --version 2>&1 | head -1))"
    else
        warn "ChromeDriver install failed — set CHROMEDRIVER_PATH manually"
    fi
fi

# ----------------------------------------------------------
# 3. PHP dependencies (Composer)
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
# 4. Frontend dependencies (npm)
# ----------------------------------------------------------
step "Installing frontend dependencies..."

cd "$PROJECT_DIR/libs/frontend"
npm install --prefer-offline --no-audit --no-fund --loglevel=warn
ok "Frontend dependencies installed"

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
echo -e "  Chromium:     $(google-chrome --version --no-sandbox 2>/dev/null || chromium --version --no-sandbox 2>/dev/null || echo 'not found')"
echo -e "  ChromeDriver: $(chromedriver --version 2>/dev/null | head -1 || echo 'not found')"
echo -e "  Node:         $(node -v 2>&1)"
echo -e "  npm:          $(npm -v 2>&1)"
echo -e "  Mago:         $("$PROJECT_DIR/vendor/bin/mago" --version 2>/dev/null || echo 'not found')"
echo ""
echo -e "  Run ${CYAN}make all${RESET} to verify everything works."
echo ""
