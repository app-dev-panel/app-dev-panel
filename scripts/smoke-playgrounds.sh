#!/usr/bin/env bash

# Smoke-test every playground that is currently serving on its expected port.
#
# Probes the homepage and `/_adp` for each playground listed in CLAUDE.md.
# Skips (with a warning) any playground that has nothing listening on its port,
# so this script is safe to run when only a subset of `make serve-*` is up.
#
# Exit codes:
#   0 — every reachable playground responded as expected (skips are warnings, not failures)
#   1 — at least one reachable playground returned an unexpected status
#   2 — `curl` is not installed

set -u

if ! command -v curl >/dev/null 2>&1; then
    echo "Error: curl not installed" >&2
    exit 2
fi

GREEN='\033[0;32m'
YELLOW='\033[0;33m'
RED='\033[0;31m'
RESET='\033[0m'

ok()    { printf "  ${GREEN}✓${RESET}  %s\n" "$1"; }
warn()  { printf "  ${YELLOW}!${RESET}  %s\n" "$1"; }
fail()  { printf "  ${RED}✗${RESET}  %s\n" "$1"; failed=$((failed + 1)); }

failed=0
checked=0

# name:port pairs — must match Makefile YII3_PORT etc.
playgrounds=(
    "yii3:8101"
    "symfony:8102"
    "yii2:8103"
    "laravel:8104"
    "spiral:8105"
)

probe() {
    local url="$1"
    curl --max-time 5 -o /dev/null -s -w "%{http_code}" "$url" 2>/dev/null || echo "000"
}

is_listening() {
    # Returns 0 if anything answers on the port within 2s.
    local port="$1"
    curl --max-time 2 -o /dev/null -s "http://127.0.0.1:${port}/" >/dev/null 2>&1
}

echo "ADP playground smoke test"
echo

for entry in "${playgrounds[@]}"; do
    name="${entry%%:*}"
    port="${entry##*:}"
    base="http://127.0.0.1:${port}"

    if ! is_listening "$port"; then
        warn "$name (port $port): not listening — \`make serve-$name\` not running, skipping"
        continue
    fi

    checked=$((checked + 1))
    code_root="$(probe "$base/")"
    code_panel="$(probe "$base/_adp")"

    if [[ "$code_root" =~ ^(2..|3..)$ ]]; then
        ok "$name $base/  → HTTP $code_root"
    else
        fail "$name $base/  → HTTP $code_root (expected 2xx/3xx)"
    fi

    if [[ "$code_panel" =~ ^(2..|3..)$ ]]; then
        ok "$name $base/_adp  → HTTP $code_panel"
    else
        fail "$name $base/_adp  → HTTP $code_panel (expected 2xx/3xx — adapter may be misconfigured)"
    fi
done

echo
if [ "$checked" -eq 0 ]; then
    warn "No playgrounds were running. Start them with \`make serve\` and rerun."
    exit 0
fi

if [ "$failed" -gt 0 ]; then
    printf "${RED}%d check(s) failed across %d playground(s).${RESET}\n" "$failed" "$checked"
    exit 1
fi

printf "${GREEN}OK${RESET} — %d playground(s) responded as expected.\n" "$checked"
exit 0
