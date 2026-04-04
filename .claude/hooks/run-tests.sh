#!/usr/bin/env bash
# Claude Code PostToolUse hook: run relevant tests after code changes
# Receives JSON via stdin with tool_input.file_path

set -euo pipefail

FILE_PATH=$(jq -r '.tool_input.file_path // empty')

if [[ -z "$FILE_PATH" || ! -f "$FILE_PATH" ]]; then
    exit 0
fi

ROOT_DIR="$(git rev-parse --show-toplevel 2>/dev/null || pwd)"

# PHP files: run PHPUnit
if [[ "$FILE_PATH" == *.php ]]; then
    # Try to find a matching test file
    TEST_FILE=""
    if [[ "$FILE_PATH" == *Test.php ]]; then
        TEST_FILE="$FILE_PATH"
    else
        # Convert src path to test path
        BASENAME=$(basename "$FILE_PATH" .php)
        TEST_FILE=$(find "$ROOT_DIR" -path "*/tests/*" -name "${BASENAME}Test.php" -type f 2>/dev/null | head -1)
    fi

    if [[ -n "$TEST_FILE" && -f "$TEST_FILE" ]]; then
        echo "Running: phpunit $TEST_FILE"
        cd "$ROOT_DIR" && php vendor/bin/phpunit "$TEST_FILE" --no-coverage 2>&1 | tail -15
    else
        echo "No matching test file found for $FILE_PATH"
    fi
    exit 0
fi

# Frontend files: run Vitest for changed file
FRONTEND_DIR="${ROOT_DIR}/libs/frontend"
if [[ "$FILE_PATH" == *.ts || "$FILE_PATH" == *.tsx || "$FILE_PATH" == *.js || "$FILE_PATH" == *.jsx ]]; then
    if [[ "$FILE_PATH" == *libs/frontend* ]]; then
        # Find matching test file
        TEST_FILE=""
        if [[ "$FILE_PATH" == *.test.ts || "$FILE_PATH" == *.test.tsx || "$FILE_PATH" == *.spec.ts || "$FILE_PATH" == *.spec.tsx ]]; then
            TEST_FILE="$FILE_PATH"
        else
            # Tests live next to source files (e.g., Foo.tsx -> Foo.test.tsx)
            DIR=$(dirname "$FILE_PATH")
            BASENAME=$(basename "$FILE_PATH" | sed 's/\.\(ts\|tsx\|js\|jsx\)$//')
            TEST_FILE=$(find "$DIR" -maxdepth 1 -name "${BASENAME}.test.*" -o -name "${BASENAME}.spec.*" 2>/dev/null | head -1)
            # Fallback: search entire frontend dir
            if [[ -z "$TEST_FILE" ]]; then
                TEST_FILE=$(find "$FRONTEND_DIR" -name "${BASENAME}.test.*" -o -name "${BASENAME}.spec.*" 2>/dev/null | head -1)
            fi
        fi

        if [[ -n "$TEST_FILE" && -f "$TEST_FILE" ]]; then
            echo "Running: vitest $TEST_FILE"
            cd "$FRONTEND_DIR" && npx vitest run "$TEST_FILE" 2>&1 | tail -15
        else
            echo "No matching test file found for $FILE_PATH"
        fi
    fi
    exit 0
fi

exit 0
