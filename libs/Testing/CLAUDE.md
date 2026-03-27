# Testing Module

Central test fixture definitions and runner for verifying ADP collectors across all adapters and playgrounds.

## Package

- Composer: `app-dev-panel/testing`
- Namespace: `AppDevPanel\Testing\`
- PHP: 8.4+
- Dependencies: `guzzlehttp/guzzle`, `guzzlehttp/psr7`, `symfony/console`

## Directory Structure

```
libs/Testing/src/
├── Fixture/
│   ├── Fixture.php             # Single test fixture definition
│   ├── Expectation.php          # Assertion about collector data
│   └── FixtureRegistry.php     # Central registry of ALL fixtures
├── Assertion/
│   ├── AssertionResult.php      # Pass/fail result
│   └── ExpectationEvaluator.php # Evaluates expectations against data
├── Runner/
│   ├── FixtureRunner.php       # HTTP-based fixture executor
│   └── FixtureResult.php       # Result of running one fixture
└── Command/
    └── DebugFixturesCommand.php # CLI command to run fixtures
libs/Testing/tests/
├── Unit/
│   ├── Fixture/
│   │   └── FixtureRegistryTest.php   # Registry structure, uniqueness, tag coverage
│   └── Assertion/
│       └── ExpectationEvaluatorTest.php # Evaluator logic for all expectation types
└── E2E/
    ├── FixtureTestCase.php       # Base PHPUnit test case for HTTP E2E
    ├── CoreFixturesTest.php      # Core collector tests (logs, events, dump, timeline)
    ├── WebFixturesTest.php       # Web context tests (request, app info)
    ├── ErrorFixturesTest.php     # Exception tests (runtime, chained)
    ├── AdvancedFixturesTest.php  # Multi-collector, heavy, http-client, filesystem
    ├── DebugApiTest.php           # Debug API endpoint contract tests
    ├── InspectorApiTest.php       # Inspector API endpoint tests
    ├── McpApiTest.php             # MCP (JSON-RPC) API endpoint tests
    └── ScenarioTest.php           # Full pipeline E2E: reset, fire all fixtures, verify API
```

## How It Works

1. **FixtureRegistry** defines all test fixtures in ONE place
2. Each **Fixture** specifies: endpoint path, HTTP method, expected collector data
3. Each playground implements the endpoint contract under `/test/fixtures/*`
4. **FixtureRunner** hits the endpoint, then queries `/debug/api/view/{id}` to verify
5. **ExpectationEvaluator** checks actual collector data against expectations

## Adding a New Fixture

1. Add a new `Fixture` to `FixtureRegistry` with expectations
2. Add the endpoint to each playground controller (Symfony, Yii2, Yiisoft)
3. Run `debug:fixtures` to verify

## Endpoint Contract

All playgrounds must implement these endpoints under `/test/fixtures/`:

| Endpoint | Fixture | Collectors Tested |
|----------|----------|-------------------|
| `/test/fixtures/logs` | logs:basic | LogCollector |
| `/test/fixtures/logs-context` | logs:context | LogCollector |
| `/test/fixtures/events` | events:basic | EventCollector |
| `/test/fixtures/dump` | var-dumper:basic | VarDumperCollector |
| `/test/fixtures/timeline` | timeline:basic | TimelineCollector |
| `/test/fixtures/request-info` | request:basic | RequestCollector, WebAppInfoCollector |
| `/test/fixtures/exception` | exception:runtime | ExceptionCollector |
| `/test/fixtures/exception-chained` | exception:chained | ExceptionCollector |
| `/test/fixtures/multi` | multi:logs-and-events | LogCollector, EventCollector, TimelineCollector |
| `/test/fixtures/logs-heavy` | logs:heavy | LogCollector |
| `/test/fixtures/http-client` | http-client:basic | HttpClientCollector |
| `/test/fixtures/filesystem` | filesystem:basic | FilesystemStreamCollector |
| `/test/fixtures/filesystem-streams` | filesystem:streams | FilesystemStreamCollector |
| `/test/fixtures/database` | database:basic | DatabaseCollector |
| `/test/fixtures/mailer` | mailer:basic | MailerCollector |
| `/test/fixtures/messenger` | messenger:basic | QueueCollector |
| `/test/fixtures/validator` | validator:basic | ValidatorCollector |
| `/test/fixtures/router` | router:basic | RouterCollector |
| `/test/fixtures/cache` | cache:basic | CacheCollector |
| `/test/fixtures/cache-heavy` | cache:heavy | CacheCollector |
| `/test/fixtures/translator` | translator:basic | TranslatorCollector |
| `/test/fixtures/request-info` | web:app-info | WebAppInfoCollector |
| `/test/fixtures/reset` | (setup) | Clears debug storage directly |
| `/test/fixtures/reset-cli` | (setup) | Clears debug storage via `debug:reset` CLI command |

The reset endpoints (`/test/fixtures/reset` and `/test/fixtures/reset-cli`) accept both GET and POST. `ScenarioTest` uses GET to clear storage before running the full fixture suite.

Yiisoft playground applies `FormatDataResponseAsJson` middleware to the entire `/test/fixtures` route group so all fixture responses return JSON.

## Running Fixtures

Two ways to run: CLI command or PHPUnit E2E tests. Both require a running playground server.

### CLI Command

```bash
debug:fixtures http://localhost:8080              # All fixtures
debug:fixtures http://localhost:8080 --tag=core   # By tag
debug:fixtures http://localhost:8080 -s logs:basic # Single fixture
debug:fixtures http://localhost:8080 --list       # List without running
debug:fixtures http://localhost:8080 -v           # Verbose assertions
```

### PHPUnit E2E Tests

```bash
# Run all fixture tests against a playground
PLAYGROUND_URL=http://127.0.0.1:8102 php vendor/bin/phpunit --testsuite Fixtures

# Run specific group
PLAYGROUND_URL=http://127.0.0.1:8102 php vendor/bin/phpunit --testsuite Fixtures --group core

# Available groups: core, web, error, advanced, api, mcp, scenario
```

### Makefile Targets

```bash
make fixtures                  # CLI fixtures against all playgrounds
make test-fixtures             # PHPUnit E2E against all playgrounds
make test-fixtures-symfony     # PHPUnit E2E against Symfony only
```
