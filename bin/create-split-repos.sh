#!/bin/bash
# Create all read-only split repositories in the app-dev-panel organization.
# Requires: gh CLI authenticated with admin:org scope.
#
# Usage:
#   ./bin/create-split-repos.sh

set -euo pipefail

ORG="app-dev-panel"
MONO_REPO="app-dev-panel/app-dev-panel"

repos=(
  "kernel:ADP Kernel — core engine: debugger lifecycle, collectors, storage, PSR proxy system"
  "api:ADP API — REST + SSE endpoints for debug data, live inspection, and ingestion"
  "cli:ADP CLI — console commands for debug server, data queries, and standalone HTTP server"
  "mcp-server:ADP MCP Server — Model Context Protocol server for AI assistant integration"
  "testing:ADP Testing — fixture definitions and runner for verifying collectors across adapters"
  "adapter-symfony:ADP Symfony Adapter — auto-wires debug collectors and inspector into Symfony"
  "adapter-laravel:ADP Laravel Adapter — auto-wires debug collectors and inspector into Laravel"
  "adapter-yii2:ADP Yii 2 Adapter — auto-wires debug collectors and inspector into Yii 2"
  "adapter-cycle:ADP Cycle ORM Adapter — database schema inspection via Cycle ORM"
  "adapter-yii3:ADP Yii 3 Adapter — auto-wires debug collectors and inspector into Yii 3"
)

echo "Creating ${#repos[@]} split repositories in ${ORG}..."
echo ""

for entry in "${repos[@]}"; do
  name="${entry%%:*}"
  desc="${entry#*:}"
  full="${ORG}/${name}"

  if gh repo view "$full" &>/dev/null; then
    echo "[skip] ${full} already exists"
  else
    gh repo create "$full" \
      --public \
      --description "${desc} (read-only split from ${MONO_REPO})"
    echo "[created] ${full}"
  fi
done

echo ""
echo "Done. Next steps:"
echo "  1. Create a fine-grained PAT with Contents:Write scope for all split repos"
echo "  2. Add it as SPLIT_TOKEN secret in ${MONO_REPO} → Settings → Secrets → Actions"
echo "  3. Register each split repo on packagist.org"
