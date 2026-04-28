#!/usr/bin/env bash
# SessionStart hook for Claude Code on the web.
#
# Delegates to the project's setup-env.sh, which is idempotent and installs
# everything `make all` needs: PCOV, Chromium + ChromeDriver, composer (root +
# every playground), and npm (libs/frontend).
#
# Only runs in remote sessions; on a local workstation the developer manages
# the toolchain themselves.

set -euo pipefail

if [ "${CLAUDE_CODE_REMOTE:-}" != "true" ]; then
    exit 0
fi

PROJECT_DIR="${CLAUDE_PROJECT_DIR:-$(cd "$(dirname "$0")/../.." && pwd)}"
exec bash "$PROJECT_DIR/setup-env.sh"
