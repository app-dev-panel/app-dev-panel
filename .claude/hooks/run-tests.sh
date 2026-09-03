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

# Hard ceiling (CLAUDE.md: TEST_TIMEOUT = 180s). Never raise it.
# GNU coreutils `timeout` (or `gtimeout` on macOS/Homebrew); skip the wrapper if neither exists.
TEST_TIMEOUT=180
TIMEOUT_BIN="$(command -v timeout 2>/dev/null || command -v gtimeout 2>/dev/null || true)"
run_with_timeout() {
    if [[ -n "$TIMEOUT_BIN" ]]; then
        "$TIMEOUT_BIN" --kill-after=5s "$TEST_TIMEOUT" "$@"
    else
        "$@"
    fi
}

# PHPUnit runner: stdin redirected from /dev/null so tests never block on / depend on the harness stdin.
run_phpunit() {
    (cd "$ROOT_DIR" && run_with_timeout php vendor/bin/phpunit "$@" --no-coverage < /dev/null 2>&1 | tail -20)
}

# PHP files: run PHPUnit
if [[ "$FILE_PATH" == *.php ]]; then
    # Skip playground files — no unit tests there
    if [[ "$REL_PATH" == playground/* ]]; then
        exit 0
    fi

    # 1. If editing a test file directly — run that file
    if [[ "$FILE_PATH" == *Test.php ]]; then
        echo "Running: phpunit $FILE_PATH"
        run_phpunit "$FILE_PATH"
        exit 0
    fi

    # 2. Try to find a matching test file by class name
    BASENAME=$(basename "$FILE_PATH" .php)
    TEST_FILE=$(find "$ROOT_DIR/libs" -path "*/tests/*" -name "${BASENAME}Test.php" -type f 2>/dev/null | head -1)

    if [[ -n "$TEST_FILE" && -f "$TEST_FILE" ]]; then
        echo "Running: phpunit $TEST_FILE"
        run_phpunit "$TEST_FILE"
        exit 0
    fi

    # 3. Fallback: determine module from file path and run whole test suite
    #    Maps file paths to PHPUnit test suite names (see phpunit.xml.dist)
    SUITE=""
    case "$REL_PATH" in
        libs/Kernel/*)          SUITE="Kernel" ;;
        libs/API/*)             SUITE="API" ;;
        libs/Cli/*)             SUITE="Cli" ;;
        libs/McpServer/*)       SUITE="McpServer" ;;
        libs/Testing/*)         SUITE="Testing" ;;
        libs/FrontendAssets/*)  SUITE="FrontendAssets" ;;
        libs/Adapter/Symfony/*) SUITE="Adapter-Symfony" ;;
        libs/Adapter/Yii3/*)    SUITE="Adapter-Yii3" ;;
        libs/Adapter/Laravel/*) SUITE="Adapter-Laravel" ;;
        libs/Adapter/Yii2/*)    SUITE="Adapter-Yii2" ;;
        libs/Adapter/Spiral/*)  SUITE="Adapter-Spiral" ;;
        libs/Adapter/Cycle/*)   SUITE="Adapter-Cycle" ;;
    esac

    if [[ -n "$SUITE" ]]; then
        echo "Running: phpunit --testsuite $SUITE"
        run_phpunit --testsuite "$SUITE"
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
                TEST_FILE=$(find "$FRONTEND_DIR" -path "*/node_modules" -prune -o \( -name "${BASENAME}.test.*" -o -name "${BASENAME}.spec.*" \) -print 2>/dev/null | head -1)
            fi
        fi

        if [[ -n "$TEST_FILE" && -f "$TEST_FILE" ]]; then
            echo "Running: vitest $TEST_FILE"
            (cd "$FRONTEND_DIR" && run_with_timeout npx vitest run "$TEST_FILE" < /dev/null 2>&1 | tail -20)
        else
            # Fallback: determine package from path and run its tests via the root
            # vitest.config.ts (projects: node + jsdom), filtered to that package.
            PACKAGE=""
            case "$REL_PATH" in
                libs/frontend/packages/panel/*)   PACKAGE="panel" ;;
                libs/frontend/packages/toolbar/*) PACKAGE="toolbar" ;;
                libs/frontend/packages/sdk/*)     PACKAGE="sdk" ;;
            esac

            if [[ -n "$PACKAGE" ]]; then
                echo "Running: vitest (package: $PACKAGE)"
                (cd "$FRONTEND_DIR" && run_with_timeout npx vitest run "packages/$PACKAGE" < /dev/null 2>&1 | tail -20)
            fi
        fi
    fi
    exit 0
fi

exit 0
