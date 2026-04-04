#!/usr/bin/env bash
# Claude Code PostToolUse hook: check module boundaries after PHP file changes
# Receives JSON via stdin with tool_input.file_path

set -euo pipefail

FILE_PATH=$(jq -r '.tool_input.file_path // empty')

if [[ -z "$FILE_PATH" || ! -f "$FILE_PATH" ]]; then
    exit 0
fi

# Only check PHP files in libs/
if [[ "$FILE_PATH" != *.php ]]; then
    exit 0
fi

ROOT_DIR="$(git rev-parse --show-toplevel 2>/dev/null || pwd)"

if [[ "$FILE_PATH" != *libs/* ]]; then
    exit 0
fi

echo "Checking module boundaries (Modulite)..."
OUTPUT=$(php "$ROOT_DIR/tools/modulite-check.php" 2>&1) || true

if echo "$OUTPUT" | grep -q "VIOLATIONS FOUND"; then
    echo "$OUTPUT" | tail -20
    exit 1
fi

echo "Modulite: all module boundaries respected."
exit 0
