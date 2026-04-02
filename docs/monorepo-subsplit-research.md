# PHP Monorepo Subsplit: Research & Options

## Current Setup (ADP)

ADP uses [danharrin/monorepo-split-github-action@v2.4.4](https://github.com/danharrin/monorepo-split-github-action) — triggers on tag push, splits 10 packages in parallel via GitHub Actions matrix. Each package directory is copied to a separate read-only repository under `app-dev-panel/` org.

---

## 1. Approaches to Subsplitting

### 1.1. `splitsh-lite` (Go binary)

**Used by**: Symfony, Laravel (illuminate/*).

**How it works**: Native Go binary that performs `git subtree split` with a persistent cache (SQLite DB tracking already-processed commits). Rewrites git history so each split repo has clean commit history of only that subdirectory.

**Pros**:
- Fastest option — Go binary, C-level git operations
- Deterministic: same input always produces same SHA1s
- Persistent cache: incremental splits are near-instant after first run
- Battle-tested on the largest PHP monorepos (Symfony: 50+ components, Laravel: 20+ packages)

**Cons**:
- Requires Go toolchain or pre-built binary
- System dependency on `libgit2`
- Not extendable from PHP
- Cache is local — running in disposable CI containers loses it every time (significant for large repos)
- No Composer tooling (only does the git split)

**Performance**: First split of a large repo can take minutes. Subsequent cached splits: seconds. For ADP's 10 packages this is fast either way.

**Maintenance**: Low once set up. Just a binary in CI.

---

### 1.2. `danharrin/monorepo-split-github-action` (Docker/PHP) — CURRENT

**Used by**: Filament PHP, ADP (current setup).

**How it works**: Docker container with PHP entrypoint. Does NOT use `git subtree split`. Instead:
1. Clones the target split repository
2. Cleans old files
3. Copies files from `package_directory` to clone
4. Commits with original message
5. Pushes to target repo (branch or tag)

Fork of `symplify/monorepo-split-github-action`.

**Pros**:
- Zero local setup — pure GitHub Action
- Simple conceptually (file copy, not git history rewrite)
- Supports both branch and tag splitting
- Matrix strategy splits all packages in parallel
- Active maintenance (Dan Harrin / Filament)

**Cons**:
- No git history preservation — split repos have flat commit history (each split = 1 commit)
- No cache — full clone + copy every time
- Docker pull overhead on each run
- Branch must exist in target repo (not auto-created)
- Slower than splitsh-lite for large repos (but fine for ADP's size)

**Performance**: Acceptable for < 20 packages. Docker pull + clone + copy + push takes ~30-60s per package (parallelized via matrix).

**Maintenance**: Minimal — just a workflow YAML.

---

### 1.3. `symplify/monorepo-builder` (PHP)

**Used by**: Rector (formerly), various PHP monorepos.

**How it works**: PHP CLI tool for monorepo management. Handles:
- `composer.json` merging across packages
- Version validation and interdependency bumping
- Release automation with configurable workers
- Branch alias management

For actual git splitting, delegates to `symplify/github-action-monorepo-split` (GitHub Action).

**Pros**:
- Written in PHP — extendable with custom release workers
- Handles the full release lifecycle (not just split)
- Composer.json merge: auto-generates root composer.json from package composer.json files
- Version bumping: updates interdependencies automatically

**Cons**:
- PHP-only — won't help with JS/frontend packages
- The split part is just a GitHub Action wrapper (same as danharrin fork)
- Extra dependency in `require-dev`
- Tomas Votruba (creator) publicly moved away from monorepos in 2022, though the tool is still maintained by the Symplify community

**Performance**: The composer tooling is fast. Split performance same as danharrin action.

**Maintenance**: Medium — PHP dependency to keep updated, config file to maintain.

---

### 1.4. `contao/monorepo-tools` (PHP)

**Used by**: Contao CMS.

**How it works**: PHP tool that splits monorepo on every commit, branch, and tag. Also merges composer.json. Unique feature: **reversible merge** — can merge multiple repos into a monorepo while preserving original commit history, and splitting back gives the same history.

**Pros**:
- Splits on every commit (not just tags) — split repos stay in sync
- Reversible merge for initial monorepo creation
- Composer.json merge command

**Cons**:
- Smaller community than splitsh-lite or symplify
- Less documentation
- PHP dependency

---

### 1.5. Native `git subtree split` (built-in)

**How it works**: `git subtree split --prefix=libs/Kernel --branch=split-kernel` then force-push to remote.

**Pros**:
- Zero dependencies — built into git since 1.7
- Preserves full commit history for the subdirectory
- Simple to understand

**Cons**:
- Slow on large repos (no caching)
- Manual scripting required for automation
- No tag handling out of the box
- Must write bash scripts for multi-package orchestration

**Performance**: Slow. Re-walks entire history each time. Unacceptable for repos with long history.

---

### 1.6. claudiodekker/splitsh-action (GitHub Action + splitsh-lite)

**How it works**: Thin GitHub Action wrapper that downloads the splitsh-lite binary and runs it. Gets the benefits of splitsh-lite (speed, history preservation, caching) with GitHub Actions convenience.

**Pros**:
- splitsh-lite performance + deterministic SHAs
- Preserves full commit history in split repos
- Native GitHub Actions integration

**Cons**:
- Requires `fetch-depth: 0` (full clone)
- Cache management needs explicit GitHub Actions cache step
- Binary download on each run (unless cached)

**Verdict**: Best option if you want history-preserving splits with minimal setup.

---

### 1.7. subtreesplit.com (SaaS)

**How it works**: Managed service by Tobias Nyholm. Uses splitsh-lite internally. Also has a bot that redirects PRs from split repos to the monorepo.

**Pros**:
- Zero maintenance
- PR redirect bot

**Cons**:
- External service dependency
- Not open-source infrastructure
- May not be suitable for private repos

---

## 2. Approaches WITHOUT Subsplitting

### 2.1. Private Packagist Multipackage

**How it works**: Private Packagist can serve individual packages from subdirectories of a monorepo. You add the repo URL, specify a glob pattern for `composer.json` files, and each becomes a separate installable package. No split repos needed.

**Pros**:
- Zero split infrastructure
- Packages available immediately after push (no sync delay)
- No version synchronization issues
- Works with private repos

**Cons**:
- **Paid service** (Private Packagist)
- Not available on public Packagist — users must have Private Packagist access
- Not suitable for open-source distribution via packagist.org

**Verdict**: Not an option for ADP (open-source project).

---

### 2.2. Composer Path Repositories (local dev only)

**How it works**: `"type": "path"` repositories in `composer.json`. Composer symlinks the local directory. ADP already uses this for development.

**Pros**:
- Zero tooling
- Instant feedback during development
- Symlinks = changes reflected immediately

**Cons**:
- Only works locally / in monorepo context
- Cannot distribute packages to external users
- `*@dev` constraints everywhere

**Verdict**: Already used by ADP for development. Not a distribution solution.

---

### 2.3. Composer Plugins

Several plugins exist:
- `beberlei/composer-monorepo-plugin` — `monorepo.json` per package, auto-resolution
- `cnastasi/monorepo-plugin` — auto-adds path repositories by scanning directories
- `meeva/composer-monorepo-builder-path-plugin` — zero-config auto-detection

**Verdict**: Development aids only. Still need subsplit for distribution.

---

## 3. How Major Projects Do It

| Project | Tool | Split Trigger | # Packages | Notes |
|---------|------|---------------|------------|-------|
| **Symfony** | Custom (splitsh-lite based) | Every commit + tag | ~60 | Fabien has custom layer on top for tags/packagist. Skips patch tags when no changes. |
| **Laravel** | splitsh-lite | Every release | ~20 | illuminate/* repos. Cache matters at this scale. |
| **Filament** | danharrin/monorepo-split-github-action | Tags | ~15 | Dan Harrin maintains the action. |
| **Contao** | contao/monorepo-tools | Every commit + tag | ~20 | Also uses it for initial repo merge. |
| **Rector** | symplify/monorepo-builder (formerly) | Tags | ~30 | Votruba split Symplify into individual repos in 2022. |
| **Yii 3** | Custom scripts | Releases | ~80 | Separate repos from the start, not a true subsplit monorepo. |

---

## 4. Key Considerations for ADP

### Current state
- 10 packages, moderate size repo
- danharrin action works, triggers on tags only
- No split on branch pushes (split repos only get tagged releases)

### What could be improved

| Issue | Options |
|-------|---------|
| **No commit history in split repos** | Switch to splitsh-lite for history preservation |
| **Split only on tags** | Add branch split (e.g., on push to `master`) so split repos stay current |
| **No composer.json validation** | Add symplify/monorepo-builder for `merge` and `validate` commands |
| **Tag sync issues** | Symfony's approach: skip patch tags when no changes in a package |
| **PR misdirection** | Add GitHub description/README to split repos pointing to monorepo |

### Recommendation

**For ADP's scale (10 packages), the current `danharrin` approach is fine.** The main improvements to consider:

1. **Add branch splitting** — split on push to `master` too (not just tags), so split repos always have latest code
2. **Add `replace` validation** — ensure root `composer.json` replace section stays in sync with packages
3. **Lock split repos** — run `bin/manage-split-repos.sh lock` to disable issues/wiki/projects
4. **Add README to split repos** — auto-generate a README pointing to the monorepo

If you later need commit history in split repos (important for `git blame`, bisect), switch to **splitsh-lite** via the [splitsh/lite](https://github.com/splitsh/lite) binary in a custom GitHub Action.

---

## Sources

- [splitsh/lite](https://github.com/splitsh/lite) — Go binary for fast subtree splitting
- [danharrin/monorepo-split-github-action](https://github.com/danharrin/monorepo-split-github-action) — GitHub Action (current ADP setup)
- [symplify/monorepo-builder](https://github.com/symplify/monorepo-builder) — PHP monorepo management tool
- [contao/monorepo-tools](https://github.com/contao/monorepo-tools) — Split + merge tools
- [Hosting PHP Packages in a Monorepo (LogRocket)](https://blog.logrocket.com/hosting-all-your-php-packages-together-in-a-monorepo/)
- [Private Packagist Multipackages](https://blog.packagist.com/installing-composer-packages-from-monorepos/)
- [Symfony: Skip empty tags](https://symfony.com/blog/symfony-packages-are-not-tagged-anymore-when-nothing-changes-between-versions)
- [Good Bye, Monorepo (Tomas Votruba)](https://tomasvotruba.com/blog/good-bye-monorepo)
- [Laravel split discussion](https://github.com/laravel/framework/discussions/51059)
- [subtreesplit.com](https://www.subtreesplit.com/) — SaaS split service
