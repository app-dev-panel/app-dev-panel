#!/usr/bin/env bash
#
# Reliable PHP built-in server wrapper for development.
#
# Features:
#   - Multi-worker support (PHP_CLI_SERVER_WORKERS)
#   - Clean shutdown on Ctrl+C (kills master + all workers)
#   - Interactive port-in-use detection with kill & restart prompt
#
# Usage: serve.sh <port> [docroot]
#   port    - TCP port to listen on (required)
#   docroot - Document root directory (default: public)
#
# Environment:
#   PHP_CLI_SERVER_WORKERS - Number of worker processes (default: 3)

set -euo pipefail

PORT=${1:?"Usage: serve.sh <port> [docroot]"}
DOCROOT=${2:-public}
WORKERS=${PHP_CLI_SERVER_WORKERS:-3}

if lsof -ti :"$PORT" >/dev/null 2>&1; then
    printf '\n  Port %s is already in use.\n  Kill and restart? [y/N] ' "$PORT"
    read -r answer
    if [ "$answer" = y ] || [ "$answer" = Y ]; then
        kill -9 $(lsof -ti :"$PORT") 2>/dev/null
        sleep 1
    else
        exit 0
    fi
fi

PHP_CLI_SERVER_WORKERS="$WORKERS" php -S "127.0.0.1:$PORT" -t "$DOCROOT" &
SERVER_PID=$!

trap "pkill -9 -P $SERVER_PID 2>/dev/null; kill -9 $SERVER_PID 2>/dev/null; exit 0" INT TERM HUP
wait $SERVER_PID 2>/dev/null
exit 0
