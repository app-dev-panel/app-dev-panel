#!/usr/bin/env bash
# Claude Code PreToolUse hook: block PR creation if tests or linting fail
# Runs full test suite and linting before allowing PR creation

set -euo pipefail

ROOT_DIR="$(git rev-parse --show-toplevel 2>/dev/null || pwd)"
ERRORS=()

echo "Pre-PR check: running tests and linting..."

# 1. PHP linting (Mago)
MAGO="${ROOT_DIR}/vendor/bin/mago"
if [[ -x "$MAGO" ]]; then
    echo "Checking PHP code quality (Mago)..."
    if ! cd "$ROOT_DIR" && "$MAGO" format --dry-run 2>&1 | tail -5; then
        ERRORS+=("Mago format check failed")
    fi
    if ! "$MAGO" lint 2>&1 | tail -5; then
        ERRORS+=("Mago lint failed")
    fi
fi

# 2. Frontend linting
FRONTEND_DIR="${ROOT_DIR}/libs/frontend"
if [[ -f "${FRONTEND_DIR}/package.json" ]]; then
    echo "Checking frontend code quality..."
    if ! cd "$FRONTEND_DIR" && npm run check 2>&1 | tail -10; then
        ERRORS+=("Frontend lint/format check failed")
    fi
fi

# 3. PHP tests
echo "Running PHP tests..."
if ! cd "$ROOT_DIR" && composer test:unit 2>&1 | tail -15; then
    ERRORS+=("PHP tests failed")
fi

# 4. Frontend tests
if [[ -f "${FRONTEND_DIR}/package.json" ]]; then
    echo "Running frontend tests..."
    if ! cd "$FRONTEND_DIR" && npm test 2>&1 | tail -15; then
        ERRORS+=("Frontend tests failed")
    fi
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
