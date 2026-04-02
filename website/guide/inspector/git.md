---
title: Git Inspector
description: "ADP Git Inspector displays repository status, branches, recent commits, and diff information."
---

# Git Inspector

View repository status, browse commit history, and perform basic Git operations.

![Git Inspector — Summary](/images/inspector/git.png)

## Summary View

| Field | Description |
|-------|-------------|
| Branch | Current branch name with checkout button |
| Last commit | SHA, message, and author of the latest commit |
| Remote | Remote name and URL |
| Status | Current `git status` output |

## Actions

| Action | Description |
|--------|-------------|
| **Checkout** | Switch to any branch (validated: alphanumeric, `/`, `.`, `-`, `_`) |
| **Pull** | Run `git pull --rebase=false` |
| **Fetch** | Run `git fetch --tags` |

## Commit Log

![Git Inspector — Log](/images/inspector/git-log.png)

Browse the last 20 commits with SHA, message, and author info.

## API Endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET | `/inspect/api/git/summary` | Branch, SHA, remotes, status |
| GET | `/inspect/api/git/log` | Last 20 commits |
| POST | `/inspect/api/git/checkout` | Switch branch |
| POST | `/inspect/api/git/command` | Run pull or fetch |

**Checkout request body:**
```json
{
    "branch": "feature/my-branch"
}
```

**Command request:**
```
POST /inspect/api/git/command?command=pull
```

::: tip
Git operations use the [Gitonomy](https://github.com/gitonomy/gitlib) library for safe repository interaction.
:::
