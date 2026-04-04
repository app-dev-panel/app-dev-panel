#!/usr/bin/env bash
# Claude Code PostToolUse hook: auto-lint changed files
# Receives JSON via stdin with tool_input.file_path

set -euo pipefail

FILE_PATH=$(jq -r '.tool_input.file_path // empty')

if [[ -z "$FILE_PATH" || ! -f "$FILE_PATH" ]]; then
    exit 0
fi

ROOT_DIR="$(git rev-parse --show-toplevel 2>/dev/null || pwd)"
MAGO="${ROOT_DIR}/vendor/bin/mago"

# PHP files: run Mago format + lint
if [[ "$FILE_PATH" == *.php ]]; then
    if [[ -x "$MAGO" ]]; then
        "$MAGO" format --file "$FILE_PATH" 2>/dev/null || true
        LINT_OUTPUT=$("$MAGO" lint --file "$FILE_PATH" 2>&1) || true
        if [[ -n "$LINT_OUTPUT" && "$LINT_OUTPUT" != *"No issues found"* ]]; then
            echo "$LINT_OUTPUT" | tail -20
        fi
    fi
    exit 0
fi

# Frontend files: run Prettier + ESLint
FRONTEND_DIR="${ROOT_DIR}/libs/frontend"
if [[ "$FILE_PATH" == *.ts || "$FILE_PATH" == *.tsx || "$FILE_PATH" == *.js || "$FILE_PATH" == *.jsx || "$FILE_PATH" == *.css ]]; then
    if [[ -f "${FRONTEND_DIR}/node_modules/.bin/prettier" ]]; then
        "${FRONTEND_DIR}/node_modules/.bin/prettier" --write "$FILE_PATH" 2>/dev/null || true
    fi
    if [[ -f "${FRONTEND_DIR}/node_modules/.bin/eslint" ]]; then
        ESLINT_OUTPUT=$("${FRONTEND_DIR}/node_modules/.bin/eslint" --fix "$FILE_PATH" 2>&1) || true
        if [[ -n "$ESLINT_OUTPUT" ]]; then
            echo "$ESLINT_OUTPUT" | tail -10
        fi
    fi
    exit 0
fi

exit 0
