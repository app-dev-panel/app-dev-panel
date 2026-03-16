# Module Reviews & Development Plans

## 1. Kernel Module Review

### Critical Issues

**1. Bug: `register_shutdown_function` in Debugger constructor (Debugger.php:29)**
- `register_shutdown_function([$this, 'shutdown'])` is called unconditionally in the constructor
- If the `Debugger` is instantiated but `startup()` is never called (e.g. ignored request), `shutdown()` still runs and calls `$this->target->flush()` with potentially uninitialized collectors
- The `$this->active` guard helps, but `shutdown()` is registered even for `withIgnored*()` clones, meaning multiple shutdown handlers exist

**2. Bug: Operator precedence in `isCommandIgnored` (Debugger.php:105)**
- `$command === null || $command === ''` returns `true` for empty commands — this silently skips debugging for commands without a name, which might be unexpected

**~~3. Bug: Variable shadowing in `FileStorage::read()` (FileStorage.php:58)~~ [DONE]**

### Major Issues

**4. Tight coupling to Yii framework (Debugger.php:11-12)**
- `Debugger` imports `Yiisoft\Yii\Console\Event\ApplicationStartup` and `Yiisoft\Yii\Http\Event\BeforeRequest`
- This makes the "framework-independent" Kernel depend on Yii's HTTP and Console packages
- Should accept generic event objects or use an adapter pattern

**~~5. `debug_backtrace()` in every proxy call~~ [DONE]**

**~~6. `LoggerInterfaceProxy` massive code duplication~~ [DONE]**

**7. `Connection` class is a God class (DebugServer/Connection.php)**
- Handles socket creation, binding, reading, broadcasting, and frame parsing all in one class
- 200+ lines with complex socket operations
- `broadcast()` uses `@fsockopen`, `@unlink` — suppressed errors hide real problems

**8. FileStorage garbage collection is not atomic (FileStorage.php:118-141)**
- `gc()` runs on every `flush()` call, scanning the filesystem with `glob()`
- On concurrent requests, multiple processes can race to delete the same files
- No file locking mechanism

**~~9. No error handling in `FileStorage::flush()` for `file_put_contents`~~ [DONE]**

### Minor Issues

**~~10. Magic numbers in Connection.php~~ [DONE]**

**~~11. `CollectorTrait::reset()` is a private empty method~~ [DONE]**

**12. `ProxyDecoratedCalls` has no return type on `__get` (ProxyDecoratedCalls.php:17)**
- Missing return type declaration

### Development Plan

| Priority | Task | Status | Impact |
|----------|------|--------|--------|
| P0 | Decouple Debugger from Yii events — accept generic events | Open | Enables multi-framework support |
| ~~P0~~ | ~~Fix variable shadowing in FileStorage::read()~~ | **Done** | ~~Prevents data corruption~~ |
| P1 | Add file locking to FileStorage | Open | Prevents race conditions |
| ~~P1~~ | ~~Optimize debug_backtrace() calls with IGNORE_ARGS + limit~~ | **Done** | ~~2-5x performance improvement~~ |
| ~~P1~~ | ~~Refactor LoggerInterfaceProxy to use LoggerTrait~~ | **Done** | ~~Eliminate code duplication~~ |
| ~~P1~~ | ~~Add error handling to file_put_contents in flush()~~ | **Done** | ~~Prevent silent data loss~~ |
| P2 | Break Connection into ConnectionFactory, SocketReader, Broadcaster | Open | Better SRP, testability |
| ~~P2~~ | ~~Make CollectorTrait::reset() protected~~ | **Done** | ~~Allow collector-specific cleanup~~ |
| P2 | Add configurable backtrace depth/toggle to proxies | Open | Performance tuning |
| ~~P3~~ | ~~Remove commented-out code from Connection.php~~ | **Done** | ~~Code cleanliness~~ |
| ~~P3~~ | ~~Replace magic error codes with named constants~~ | **Done** | ~~Readability~~ |

---

## 2. API Module Review

### Critical Issues

**1. SECURITY: Arbitrary command execution (CommandController.php:61-116)**
- `BashCommand` executes arbitrary commands from user input via query params
- While it relies on a configured `commandMap`, composer scripts are loaded dynamically
- IP filtering exists but is the only defense — no CSRF protection, no auth token

**2. SECURITY: Path traversal in `InspectController::files()` (InspectController.php:148-226)**
- `$path = $request['path'] ?? ''` comes from user input
- `realpath()` is used but AFTER string manipulation
- Symlinks could escape the root path check

**3. SECURITY: Arbitrary class instantiation (InspectController.php:261-282)**
- `$className = $queryParams['classname']` — user-controlled class name
- `$container->get($className)` — instantiates any class available in the DI container

**4. SECURITY: SSRF via request replay (InspectController.php:369-387)**
- `request()` method replays HTTP requests using `new Client()` (GuzzleHttp)
- No URL validation, no allowlist

**~~5. Bug: Operator precedence in all Command classes~~ [DONE]**

### Major Issues

**6. `InspectController` is a God class (490 lines, 15+ methods)**
- Should be split into dedicated controllers per domain

**7. `ServerSentEventsStream::read()` uses `sleep(1)` (DebugController.php:164)**
- Blocks the PHP process for 1 second per poll cycle
- With `maxRetries = 10`, a client connection ties up a PHP worker for 10+ seconds

**8. `ApplicationState` uses global mutable state (ApplicationState.php)**
- `public static array $params = []` — global mutable state accessed from controllers

**9. Response format inconsistency**
- Some methods return `ResponseInterface`, some return `DataResponse`
- `eventListeners()` has no return type at all

**10. `ModuleFederationAssetBundle` — empty/stub class (ModuleFederationAssetBundle.php)**
- Likely placeholder that was never implemented

**11. DoS: Missing database pagination (DbSchemaProvider.php, CycleSchemaProvider.php)**
- `$records = new Query($this->db)->from($schema->getName())->all()` reads ALL records into memory
- No LIMIT parameter, no pagination support

**~~12. Null dereference in createJsPanelResponse~~ [DONE]**

**13. Git branch name injection (GitController.php:71-75)**
- Branch name from user input passed directly to `$git->getWorkingCopy()->checkout($branch)`
- No sanitization for special characters

**14. Translation write without path validation (InspectController.php:96-138)**
- `$locale` parameter from user input not validated for `../` traversal

**~~15. Silent exception swallowing (InspectController.php:81-89)~~ [DONE]**

### Minor Issues

**16. Hardcoded 'Yiisoft\Yii\Debug' in filter patterns (InspectController.php:238)**
- References old namespace, should use AppDevPanel namespace

**17. TODO comments in production code**
- DebugController.php:124: "TODO implement OS signal handling"
- InspectController.php:231: "TODO: how to get params for console"
- InspectController.php:395: "TODO: change events-web"

### Development Plan

| Priority | Task | Status | Impact |
|----------|------|--------|--------|
| ~~P0~~ | ~~Fix operator precedence bug in ALL commands~~ | **Done** | ~~Critical bug — FAIL status never returned~~ |
| P0 | Add authentication/authorization to inspector endpoints | Open | Security — prevent unauthorized access |
| P0 | Validate and sanitize class names in object() endpoint | Open | Security — prevent arbitrary class instantiation |
| P0 | Add URL allowlist to request replay endpoint | Open | Security — prevent SSRF |
| P0 | Add path traversal protection to files() endpoint | Open | Security — prevent directory escape |
| P0 | Add pagination/LIMIT to database queries | Open | Security — prevent DoS |
| P0 | Validate git branch names before checkout | Open | Security — prevent injection |
| P0 | Validate locale in translation write endpoint | Open | Security — prevent path traversal |
| P1 | Split InspectController into domain-specific controllers | Open | Maintainability, SRP |
| P1 | Replace polling SSE with real event-driven approach | Open | Performance — free up PHP workers |
| P1 | Replace ApplicationState with proper DI | Open | Remove global state |
| ~~P1~~ | ~~Fix null dereference in createJsPanelResponse~~ | **Done** | ~~Stability — prevent TypeError~~ |
| ~~P1~~ | ~~Replace empty catch blocks with logging~~ | **Done** | ~~Debuggability~~ |
| P2 | Add CSRF protection to mutation endpoints | Open | Security hardening |
| P2 | Add return type to eventListeners() | Open | Type safety |
| P2 | Replace hardcoded old namespace in class filters | Open | Correctness |
| P3 | Implement or remove ModuleFederationAssetBundle | Open | Clean up dead code |
| P3 | Resolve TODO comments | Open | Code quality |

---

## 3. CLI Module Review

### Critical Issues

**~~1. No error handling for socket operations~~ [DONE]**

**2. DebugServerBroadcastCommand logic flaw (lines 45-57)**
- Creates socket, registers signal handler, immediately broadcasts and returns
- Signal handler never gets a chance to run
- Socket is never explicitly closed on success path (only on SIGINT)

**3. Unhandled JsonException (DebugServerCommand.php:74)**
- `json_decode()` with `JSON_THROW_ON_ERROR` has no try-catch — malformed JSON crashes the server loop

### Major Issues

**4. Hardcoded defaults (DebugServerCommand.php:26-27)**
- Default address `0.0.0.0` and port `8890` are hardcoded
- Not configurable via DI or config files

**5. Inconsistent command registration patterns**
- `DebugResetCommand` uses `#[AsCommand]` attribute
- `DebugServerCommand` and `DebugServerBroadcastCommand` use `protected static $defaultName`
- Mixing old and new Symfony Console patterns

**6. Test namespace mismatch (ResetCommandTest.php:5)**
- Namespace is `Unit\Command` instead of `AppDevPanel\Cli\Tests\Unit\Command`

### Development Plan

| Priority | Task | Status | Impact |
|----------|------|--------|--------|
| ~~P0~~ | ~~Add error handling to socket operations~~ | **Done** | ~~Stability — prevent unhandled crashes~~ |
| P0 | Fix DebugServerBroadcastCommand socket lifecycle | Open | Correctness — currently broken |
| P1 | Add try-catch for JSON decode in server loop | Open | Stability — malformed data resilience |
| P1 | Add signal handling (SIGTERM, SIGINT) to all server commands | Open | Stability — clean shutdown |
| P2 | Make address/port configurable via DI config | Open | Flexibility for deployment |
| P2 | Standardize on `#[AsCommand]` attribute pattern | Open | Consistency |
| P2 | Fix test namespace | Open | Autoloader correctness |
| P3 | Add logging to debug server operations | Open | Debugging server issues |

---

## 4. Adapter/Yiisoft Module Review

### Issues

**1. Bootstrap phase creates socket (bootstrap.php)**
- VarDumper handler replacement happens at bootstrap, creating sockets early
- If the debug server isn't running, this silently fails

**2. Configuration coupling to params key**
- All config uses `'app-dev-panel/yii-debug'` prefix but still references `'yii-debug'` in some places
- Namespace migration is incomplete

**3. Event mapping is fragile**
- Events depend on specific Yii event class names
- No validation that event classes exist

**4. DebugServiceProvider creates circular dependency potential**
- Wrapping `ContainerInterface` with `ContainerInterfaceProxy` that calls `$container->get(ContainerProxyConfig::class)` during container resolution

**~~5. Bug: Command name mismatch in ignoredCommands (params.php:90)~~ [DONE]**

**~~6. Duplicated enabled-check across all config files~~ [DONE]**

### Development Plan

| Priority | Task | Status | Impact |
|----------|------|--------|--------|
| ~~P0~~ | ~~Fix command name mismatch: `debug/reset` → `debug:reset`~~ | **Done** | ~~Bug — ignored commands not working~~ |
| P1 | Complete namespace migration (yii-debug → app-dev-panel) | Open | Consistency |
| P1 | Add guard against circular dependency in DebugServiceProvider | Open | Stability |
| ~~P1~~ | ~~Extract duplicated enabled-check to shared helper~~ | **Done** | ~~DRY, maintainability~~ |
| P2 | Lazy-initialize VarDumper handler to avoid early socket creation | Open | Performance |
| P2 | Document adapter creation guide for Symfony/Laravel | Open | Ecosystem growth |
| P3 | Add integration tests with real Yii DI container | Open | Test coverage |

---

## 5. Frontend (app-dev-panel) Module Review

### Architecture Issues

**1. Monorepo without shared type definitions**
- Three packages (app-dev-panel, app-dev-panel-sdk, app-dev-toolbar) share types informally
- No shared type package or generated API types from backend

**2. RTK Query API definitions are manually maintained**
- API endpoint definitions don't auto-generate from backend routes
- Risk of frontend/backend drift

**3. Large bundle size from module system**
- All modules are loaded upfront, no code splitting
- ModuleInterface pattern doesn't support lazy loading

### Performance Issues

**4. SSE reconnection is aggressive**
- On connection loss, immediate reconnection without backoff
- Can overwhelm server with connection attempts

**5. No virtualization for large data tables**
- Debug entry lists, log viewers render all rows
- Performance degrades with 1000+ entries

### Design Issues

**6. Inconsistent error handling**
- Some components use ErrorBoundary, most don't
- API errors are silently swallowed in many places

**7. Toolbar cross-window communication uses postMessage without origin validation**
- Security risk if embedded in untrusted contexts

### Development Plan

| Priority | Task | Status | Impact |
|----------|------|--------|--------|
| P1 | Add code splitting / lazy loading for modules | Open | Performance — smaller initial bundle |
| P1 | Add exponential backoff to SSE reconnection | Open | Stability — prevent server overload |
| P1 | Generate TypeScript types from backend API | Open | Type safety, prevent drift |
| P2 | Add virtualization for large lists (react-window) | Open | Performance with large datasets |
| P2 | Add ErrorBoundary to all route-level components | Open | Stability — prevent full app crash |
| P2 | Validate postMessage origin in toolbar communication | Open | Security |
| P3 | Add shared types package to monorepo | Open | Code organization |
| P3 | Add frontend unit tests with Vitest | Open | Test coverage |
| P3 | Add E2E tests with Playwright | Open | Integration test coverage |

---

## Cross-Module Issues

### 1. Namespace Migration Incomplete
Old references to `Yiisoft\Yii\Debug\` remain in:
- InspectController.php filter patterns
- Test data expectations
- Config references

### 2. No Versioning Strategy
- No API versioning for backend endpoints
- No compatibility matrix between frontend and backend versions

### 3. Missing Observability
- No structured logging in the debugger itself
- No metrics collection (how long does flush take? how many collectors? storage size?)
- The debugger can't debug itself

### 4. Documentation Gaps
- No API reference documentation (OpenAPI/Swagger)
- No architecture decision records (ADRs)
- No contribution guide

### Priority Matrix (All Modules) — Updated

| Priority | Total | Done | Remaining | Theme |
|----------|-------|------|-----------|-------|
| P0 (Critical) | 12 | 4 | 8 | Security fixes, critical bugs |
| P1 (High) | 17 | 7 | 10 | Architecture improvements, performance, stability |
| P2 (Medium) | 14 | 1 | 13 | Design improvements, hardening |
| P3 (Low) | 10 | 2 | 8 | Code quality, documentation |
| **Total** | **53** | **14** | **39** | |
