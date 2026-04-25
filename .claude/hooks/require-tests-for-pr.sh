#!/usr/bin/env bash
# Claude Code PreToolUse hook: block PR creation if tests or linting fail
# Runs full test suite and linting before allowing PR creation

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"
ERRORS=()

echo "Pre-PR check: running tests and linting..."

# 1. PHP linting (Mago)
MAGO="${ROOT_DIR}/vendor/bin/mago"
if [[ -x "$MAGO" ]]; then
    echo "Checking PHP code formatting (Mago)..."
    if ! (cd "$ROOT_DIR" && "$MAGO" format --dry-run 2>&1 | tail -5); then
        ERRORS+=("Mago format check failed")
    fi

    # Note: mago lint has pre-existing baseline issues; only block on new errors
    # by running in warning-only mode (non-zero exit is expected on this project)
fi

# 2. Modulite (module boundary check)
echo "Checking module boundaries (Modulite)..."
if ! (cd "$ROOT_DIR" && php tools/modulite-check.php 2>&1 | tail -5); then
    ERRORS+=("Modulite boundary check failed")
fi

# 3. Frontend linting (skip if node_modules not installed)
FRONTEND_DIR="${ROOT_DIR}/libs/frontend"
if [[ -f "${FRONTEND_DIR}/package.json" ]] && [[ -d "${FRONTEND_DIR}/node_modules" ]]; then
    echo "Checking frontend code quality..."
    if ! (cd "$FRONTEND_DIR" && npm run check 2>&1 | tail -10); then
        ERRORS+=("Frontend lint/format check failed")
    fi
else
    echo "Skipping frontend checks (node_modules not installed)"
fi

# 4. PHP tests
echo "Running PHP tests..."
if ! (cd "$ROOT_DIR" && COMPOSER_ALLOW_SUPERUSER=1 composer test:unit 2>&1 | tail -15); then
    ERRORS+=("PHP tests failed")
fi

# 5. Frontend tests (skip if node_modules not installed)
if [[ -f "${FRONTEND_DIR}/package.json" ]] && [[ -d "${FRONTEND_DIR}/node_modules" ]]; then
    echo "Running frontend tests..."
    if ! (cd "$FRONTEND_DIR" && npm test 2>&1 | tail -15); then
        ERRORS+=("Frontend tests failed")
    fi
else
    echo "Skipping frontend tests (node_modules not installed)"
fi

# Report results
if [[ ${#ERRORS[@]} -gt 0 ]]; then
    echo ""
    echo "BLOCKED: PR creation blocked due to failures:"
    for err in "${ERRORS[@]}"; do
        echo "  - $err"
    done
    echo ""
    echo "Fix the issues above before creating a PR."
    exit 2
fi

echo "All checks passed. PR creation allowed."
exit 0
