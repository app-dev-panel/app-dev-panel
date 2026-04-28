#!/usr/bin/env bash
# SessionStart hook for Claude Code on the web.
#
# Delegates to the project's setup-env.sh, which is idempotent and installs
# everything `make all` needs: PCOV, Chromium + ChromeDriver, composer (root +
# every playground), and npm (libs/frontend).
#
# Runs asynchronously (5 min budget): the agent loop starts immediately, the
# install proceeds in the background. If the agent runs `make test` before
# install finishes it will block on the npm/composer locks — acceptable
# trade-off for instant session start. Setup-env.sh is idempotent, so a
# subsequent session reusing the cached container reruns it cheaply.
#
# Only runs in remote sessions; on a local workstation the developer manages
# the toolchain themselves.

set -euo pipefail

if [ "${CLAUDE_CODE_REMOTE:-}" != "true" ]; then
    exit 0
fi

echo '{"async": true, "asyncTimeout": 300000}'

PROJECT_DIR="${CLAUDE_PROJECT_DIR:-$(cd "$(dirname "$0")/../.." && pwd)}"
exec bash "$PROJECT_DIR/setup-env.sh"
