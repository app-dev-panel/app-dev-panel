#!/usr/bin/env bash

# ADP environment doctor.
#
# Verifies that the local machine has every dependency required to run
# `make all`: PHP 8.4+ with PCOV, Composer, Node 20+, npm, GNU `timeout`,
# and (when present) ChromeDriver matching the installed Chrome major version.
#
# Exit codes:
#   0 — all required checks pass (warnings allowed)
#   1 — at least one required check failed

set -u

GREEN='\033[0;32m'
YELLOW='\033[0;33m'
RED='\033[0;31m'
RESET='\033[0m'

failed=0
warned=0

ok()    { printf "  ${GREEN}✓${RESET}  %s\n" "$1"; }
warn()  { printf "  ${YELLOW}!${RESET}  %s\n" "$1"; warned=$((warned + 1)); }
fail()  { printf "  ${RED}✗${RESET}  %s\n" "$1"; failed=$((failed + 1)); }

require_cmd() {
    local cmd="$1"; local hint="${2:-}"
    if command -v "$cmd" >/dev/null 2>&1; then
        ok "$cmd: $(command -v "$cmd")"
    else
        fail "$cmd: not found${hint:+ — $hint}"
    fi
}

echo "ADP doctor — environment preflight"
echo

echo "Required tools:"
require_cmd php       "install PHP 8.4 or newer"
require_cmd composer  "https://getcomposer.org/"
require_cmd node      "install Node.js 20 LTS or newer"
require_cmd npm       "ships with Node.js"
require_cmd make
if command -v timeout >/dev/null 2>&1; then
    ok "timeout: $(command -v timeout)"
elif command -v gtimeout >/dev/null 2>&1; then
    ok "gtimeout: $(command -v gtimeout)"
else
    fail "timeout / gtimeout not found — install GNU coreutils (brew install coreutils on macOS)"
fi

echo
echo "Versions:"
if command -v php >/dev/null 2>&1; then
    php_version="$(php -r 'echo PHP_VERSION;')"
    php_major_minor="$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')"
    if php -r "exit(version_compare(PHP_VERSION, '8.4.0', '>=') ? 0 : 1);"; then
        ok "PHP $php_version (>= 8.4 required)"
    else
        fail "PHP $php_version — CLAUDE.md requires 8.4+"
    fi

    if php -m | grep -qi '^pcov$'; then
        ok "pcov extension loaded (line coverage)"
    elif php -m | grep -qi '^xdebug$'; then
        warn "xdebug loaded — coverage works but is slow; prefer pcov"
    else
        warn "no coverage driver — \`phpunit --coverage-*\` will fail. Install pcov: pecl install pcov"
    fi
fi

if command -v node >/dev/null 2>&1; then
    node_major="$(node -p 'process.versions.node.split(".")[0]')"
    if [ "$node_major" -ge 20 ]; then
        ok "Node.js $(node -v) (>= 20 required)"
    else
        fail "Node.js $(node -v) — need 20 or newer"
    fi
fi

echo
echo "Optional (frontend e2e):"
if command -v chromedriver >/dev/null 2>&1; then
    cd_version="$(chromedriver --version 2>/dev/null | awk '{print $2}')"
    cd_major="${cd_version%%.*}"
    ok "chromedriver $cd_version"

    chrome_bin=""
    for c in google-chrome chromium chromium-browser chrome; do
        if command -v "$c" >/dev/null 2>&1; then
            chrome_bin="$c"
            break
        fi
    done

    if [ -n "$chrome_bin" ]; then
        chrome_version="$($chrome_bin --version 2>/dev/null | awk '{print $NF}')"
        chrome_major="${chrome_version%%.*}"
        ok "$chrome_bin $chrome_version"
        if [ -n "$chrome_major" ] && [ -n "$cd_major" ] && [ "$chrome_major" = "$cd_major" ]; then
            ok "Chrome and ChromeDriver major versions match ($chrome_major)"
        else
            warn "Chrome major ($chrome_major) ≠ ChromeDriver major ($cd_major) — frontend e2e will fail"
        fi
    else
        warn "Chrome/Chromium not found — install for frontend e2e"
    fi
else
    warn "chromedriver not found — frontend e2e (\`make test-frontend-e2e\`) will be skipped"
fi

echo
if [ "$failed" -gt 0 ]; then
    printf "${RED}%d failure(s), %d warning(s).${RESET}\n" "$failed" "$warned"
    exit 1
fi
printf "${GREEN}OK${RESET} (%d warning(s)).\n" "$warned"
exit 0
