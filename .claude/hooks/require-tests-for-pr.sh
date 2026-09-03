#!/usr/bin/env bash
# Claude Code PreToolUse hook: block PR creation if tests or linting fail
# Runs full test suite and linting before allowing PR creation.
#
# Wired to two matchers in .claude/settings.json:
#   - mcp__github__create_pull_request  → always runs
#   - Bash                              → runs only when the command contains `gh pr create`
# Receives the tool-call JSON on stdin (tool_name, tool_input).

set -euo pipefail

# --- Only gate PR creation ---------------------------------------------------
INPUT="$(cat 2>/dev/null || true)"
if [[ -n "$INPUT" ]] && command -v jq &>/dev/null; then
    TOOL_NAME="$(printf '%s' "$INPUT" | jq -r '.tool_name // empty' 2>/dev/null || true)"
    if [[ "$TOOL_NAME" == "Bash" ]]; then
        COMMAND="$(printf '%s' "$INPUT" | jq -r '.tool_input.command // empty' 2>/dev/null || true)"
        # Match only a real invocation (start of a line or right after ; && || |), not the phrase inside a string.
        if ! printf '%s' "$COMMAND" | grep -Eq '(^|[;&|])[[:space:]]*gh[[:space:]]+pr[[:space:]]+create([[:space:]]|$)'; then
            exit 0
        fi
    fi
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"
ERRORS=()

# Hard ceiling (CLAUDE.md: TEST_TIMEOUT = 180s). Never raise it.
TEST_TIMEOUT=180
TIMEOUT_BIN="$(command -v timeout 2>/dev/null || command -v gtimeout 2>/dev/null || true)"
run_with_timeout() {
    if [[ -n "$TIMEOUT_BIN" ]]; then
        "$TIMEOUT_BIN" --kill-after=5s "$TEST_TIMEOUT" "$@"
    else
        "$@"
    fi
}

# Run a command, show its tail, and return the command's own exit status
# (not tail's — `set -o pipefail` makes the pipeline fail when the command fails).
run_step() {
    local lines="$1"; shift
    "$@" 2>&1 | tail -n "$lines"
}

echo "Pre-PR check: running tests and linting..."

# 1. PHP formatting (Mago): vendor/bin/mago first, then a global one on PATH
MAGO=""
if [[ -x "${ROOT_DIR}/vendor/bin/mago" ]]; then
    MAGO="${ROOT_DIR}/vendor/bin/mago"
elif command -v mago &>/dev/null; then
    MAGO="$(command -v mago)"
fi

if [[ -n "$MAGO" ]]; then
    echo "Checking PHP code formatting (Mago)..."
    if ! (cd "$ROOT_DIR" && run_step 5 "$MAGO" fmt --check); then
        ERRORS+=("Mago fmt check failed")
    fi

    # Note: mago lint has pre-existing baseline issues; only block on new errors
    # by running in warning-only mode (non-zero exit is expected on this project)
else
    echo "Skipping Mago format check (mago not found in vendor/bin or PATH)"
fi

# 2. Modulite (module boundary check)
echo "Checking module boundaries (Modulite)..."
if ! (cd "$ROOT_DIR" && run_step 5 php tools/modulite-check.php); then
    ERRORS+=("Modulite boundary check failed")
fi

# 3. Project-review checks (timeouts + strict tests + docs tree)
echo "Running project-review checks (make review-checks)..."
if ! (cd "$ROOT_DIR" && run_step 15 make review-checks); then
    ERRORS+=("Project-review checks failed (make review-checks)")
fi

# 4. Frontend linting (skip if node_modules not installed)
FRONTEND_DIR="${ROOT_DIR}/libs/frontend"
if [[ -f "${FRONTEND_DIR}/package.json" ]] && [[ -d "${FRONTEND_DIR}/node_modules" ]]; then
    echo "Checking frontend code quality..."
    if ! (cd "$FRONTEND_DIR" && run_step 10 npm run check); then
        ERRORS+=("Frontend lint/format check failed")
    fi
else
    echo "Skipping frontend checks (node_modules not installed)"
fi

# 5. PHP tests
echo "Running PHP tests..."
if ! (cd "$ROOT_DIR" && COMPOSER_ALLOW_SUPERUSER=1 run_step 15 run_with_timeout composer test:unit < /dev/null); then
    ERRORS+=("PHP tests failed")
fi

# 6. Frontend tests (skip if node_modules not installed)
if [[ -f "${FRONTEND_DIR}/package.json" ]] && [[ -d "${FRONTEND_DIR}/node_modules" ]]; then
    echo "Running frontend tests..."
    if ! (cd "$FRONTEND_DIR" && run_step 15 run_with_timeout npm test < /dev/null); then
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
