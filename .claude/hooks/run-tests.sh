#!/usr/bin/env bash
# Claude Code PostToolUse hook: run relevant tests after code changes
# Receives JSON via stdin with tool_input.file_path

set -euo pipefail

FILE_PATH=$(jq -r '.tool_input.file_path // empty')

if [[ -z "$FILE_PATH" || ! -f "$FILE_PATH" ]]; then
    exit 0
fi

ROOT_DIR="$(git rev-parse --show-toplevel 2>/dev/null || pwd)"

# Resolve path relative to root
REL_PATH="${FILE_PATH#"$ROOT_DIR"/}"

# PHP files: run PHPUnit
if [[ "$FILE_PATH" == *.php ]]; then
    # Skip playground files — no unit tests there
    if [[ "$REL_PATH" == playground/* ]]; then
        exit 0
    fi

    # 1. If editing a test file directly — run that file
    if [[ "$FILE_PATH" == *Test.php ]]; then
        echo "Running: phpunit $FILE_PATH"
        cd "$ROOT_DIR" && php vendor/bin/phpunit "$FILE_PATH" --no-coverage 2>&1 | tail -20
        exit 0
    fi

    # 2. Try to find a matching test file by class name
    BASENAME=$(basename "$FILE_PATH" .php)
    TEST_FILE=$(find "$ROOT_DIR/libs" -path "*/tests/*" -name "${BASENAME}Test.php" -type f 2>/dev/null | head -1)

    if [[ -n "$TEST_FILE" && -f "$TEST_FILE" ]]; then
        echo "Running: phpunit $TEST_FILE"
        cd "$ROOT_DIR" && php vendor/bin/phpunit "$TEST_FILE" --no-coverage 2>&1 | tail -20
        exit 0
    fi

    # 3. Fallback: determine module from file path and run whole test suite
    #    Maps file paths to PHPUnit test suite names
    SUITE=""
    case "$REL_PATH" in
        libs/Kernel/*)          SUITE="Kernel" ;;
        libs/API/*)             SUITE="API" ;;
        libs/Cli/*)             SUITE="Cli" ;;
        libs/McpServer/*)       SUITE="McpServer" ;;
        libs/Testing/*)         SUITE="Testing" ;;
        libs/Adapter/Symfony/*) SUITE="Adapter-Symfony" ;;
        libs/Adapter/Yii3/*)   SUITE="Adapter-Yii3" ;;
        libs/Adapter/Laravel/*) SUITE="Adapter-Laravel" ;;
        libs/Adapter/Yii2/*)   SUITE="Adapter-Yii2" ;;
        libs/Adapter/Cycle/*)  SUITE="Adapter-Cycle" ;;
    esac

    if [[ -n "$SUITE" ]]; then
        echo "Running: phpunit --testsuite $SUITE"
        cd "$ROOT_DIR" && php vendor/bin/phpunit --testsuite "$SUITE" --no-coverage 2>&1 | tail -20
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
            TEST_FILE=$(find "$DIR" -maxdepth 1 \( -name "${BASENAME}.test.*" -o -name "${BASENAME}.spec.*" \) 2>/dev/null | head -1)
            # Fallback: search entire frontend dir
            if [[ -z "$TEST_FILE" ]]; then
                TEST_FILE=$(find "$FRONTEND_DIR" \( -name "${BASENAME}.test.*" -o -name "${BASENAME}.spec.*" \) 2>/dev/null | head -1)
            fi
        fi

        if [[ -n "$TEST_FILE" && -f "$TEST_FILE" ]]; then
            echo "Running: vitest $TEST_FILE"
            cd "$FRONTEND_DIR" && npx vitest run "$TEST_FILE" 2>&1 | tail -20
        else
            # Fallback: determine package from path and run its tests
            PACKAGE=""
            case "$REL_PATH" in
                libs/frontend/packages/panel/*)   PACKAGE="panel" ;;
                libs/frontend/packages/toolbar/*) PACKAGE="toolbar" ;;
                libs/frontend/packages/sdk/*)     PACKAGE="sdk" ;;
            esac

            if [[ -n "$PACKAGE" ]]; then
                echo "Running: vitest (package: $PACKAGE)"
                cd "$FRONTEND_DIR" && npx lerna run test --scope="@adp/$PACKAGE" -- --run 2>&1 | tail -20
            fi
        fi
    fi
    exit 0
fi

exit 0
