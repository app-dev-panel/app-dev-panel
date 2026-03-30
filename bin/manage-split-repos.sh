#!/bin/bash
# Manage all read-only split repositories in the app-dev-panel organization.
# Requires: gh CLI authenticated with admin:org scope.
#
# Usage:
#   ./bin/manage-split-repos.sh <command>
#
# Commands:
#   disable-issues      Disable issues on all split repos
#   enable-issues       Enable issues on all split repos
#   disable-projects    Disable projects on all split repos
#   enable-projects     Enable projects on all split repos
#   disable-wiki        Disable wiki on all split repos
#   enable-wiki         Enable wiki on all split repos
#   lock                Disable issues, projects, and wiki (recommended for read-only splits)
#   unlock              Enable issues, projects, and wiki
#   status              Show current settings for all split repos

set -euo pipefail

ORG="app-dev-panel"

REPOS=(
  kernel
  api
  cli
  mcp-server
  testing
  adapter-symfony
  adapter-laravel
  adapter-yii2
  adapter-cycle
  adapter-yiisoft
)

usage() {
  sed -n '2,/^$/s/^# \?//p' "$0"
  exit 1
}

patch_repos() {
  local payload="$1"
  local description="$2"

  echo "${description} for ${#REPOS[@]} repos in ${ORG}..."
  echo ""

  for repo in "${REPOS[@]}"; do
    if gh api "repos/${ORG}/${repo}" --method PATCH --input - <<< "$payload" > /dev/null 2>&1; then
      echo "  ✓ ${ORG}/${repo}"
    else
      echo "  ✗ ${ORG}/${repo} — failed"
    fi
  done

  echo ""
  echo "Done."
}

show_status() {
  echo "Repository settings for ${ORG}:"
  echo ""
  printf "  %-25s %-10s %-10s %-10s\n" "REPO" "ISSUES" "PROJECTS" "WIKI"
  printf "  %-25s %-10s %-10s %-10s\n" "----" "------" "--------" "----"

  for repo in "${REPOS[@]}"; do
    result=$(gh api "repos/${ORG}/${repo}" --jq '[.has_issues, .has_projects, .has_wiki] | @tsv' 2>/dev/null) || {
      printf "  %-25s %-10s\n" "${repo}" "NOT FOUND"
      continue
    }
    read -r issues projects wiki <<< "$result"
    printf "  %-25s %-10s %-10s %-10s\n" "${repo}" "${issues}" "${projects}" "${wiki}"
  done
}

command="${1:-}"

case "$command" in
  disable-issues)
    patch_repos '{"has_issues": false}' "Disabling issues"
    ;;
  enable-issues)
    patch_repos '{"has_issues": true}' "Enabling issues"
    ;;
  disable-projects)
    patch_repos '{"has_projects": false}' "Disabling projects"
    ;;
  enable-projects)
    patch_repos '{"has_projects": true}' "Enabling projects"
    ;;
  disable-wiki)
    patch_repos '{"has_wiki": false}' "Disabling wiki"
    ;;
  enable-wiki)
    patch_repos '{"has_wiki": true}' "Enabling wiki"
    ;;
  lock)
    patch_repos '{"has_issues": false, "has_projects": false, "has_wiki": false}' "Locking (disabling issues, projects, wiki)"
    ;;
  unlock)
    patch_repos '{"has_issues": true, "has_projects": true, "has_wiki": true}' "Unlocking (enabling issues, projects, wiki)"
    ;;
  status)
    show_status
    ;;
  *)
    usage
    ;;
esac
