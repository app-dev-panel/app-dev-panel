# Testing Module

Central test scenario definitions and runner for verifying ADP collectors across all adapters and playgrounds.

## Package

- Composer: `app-dev-panel/testing`
- Namespace: `AppDevPanel\Testing\`
- PHP: 8.4+
- Dependencies: `guzzlehttp/guzzle`, `guzzlehttp/psr7`, `symfony/console`

## Directory Structure

```
libs/Testing/src/
├── Scenario/
│   ├── Scenario.php             # Single test scenario definition
│   ├── Expectation.php          # Assertion about collector data
│   └── ScenarioRegistry.php     # Central registry of ALL scenarios
├── Assertion/
│   ├── AssertionResult.php      # Pass/fail result
│   └── ExpectationEvaluator.php # Evaluates expectations against data
├── Runner/
│   ├── ScenarioRunner.php       # HTTP-based scenario executor
│   └── ScenarioResult.php       # Result of running one scenario
└── Command/
    └── DebugScenariosCommand.php # CLI command to run scenarios
libs/Testing/tests/
└── E2E/
    ├── ScenarioTestCase.php       # Base PHPUnit test case for HTTP E2E
    ├── CoreScenariosTest.php      # Core collector tests (logs, events, dump, timeline)
    ├── WebScenariosTest.php       # Web context tests (request, app info)
    ├── ErrorScenariosTest.php     # Exception tests (runtime, chained)
    ├── AdvancedScenariosTest.php  # Multi-collector, heavy, http-client, filesystem
    └── DebugApiTest.php           # Debug API endpoint contract tests
```

## How It Works

1. **ScenarioRegistry** defines all test scenarios in ONE place
2. Each **Scenario** specifies: endpoint path, HTTP method, expected collector data
3. Each playground implements the endpoint contract under `/test/scenarios/*`
4. **ScenarioRunner** hits the endpoint, then queries `/debug/api/view/{id}` to verify
5. **ExpectationEvaluator** checks actual collector data against expectations

## Adding a New Scenario

1. Add a new `Scenario` to `ScenarioRegistry` with expectations
2. Add the endpoint to each playground controller (Symfony, Yii2, Yiisoft)
3. Run `debug:scenarios` to verify

## Endpoint Contract

All playgrounds must implement these endpoints under `/test/scenarios/`:

| Endpoint | Scenario | Collectors Tested |
|----------|----------|-------------------|
| `/test/scenarios/logs` | logs:basic | LogCollector |
| `/test/scenarios/logs-context` | logs:context | LogCollector |
| `/test/scenarios/events` | events:basic | EventCollector |
| `/test/scenarios/dump` | var-dumper:basic | VarDumperCollector |
| `/test/scenarios/timeline` | timeline:basic | TimelineCollector |
| `/test/scenarios/request-info` | request:basic | RequestCollector, WebAppInfoCollector |
| `/test/scenarios/exception` | exception:runtime | ExceptionCollector |
| `/test/scenarios/exception-chained` | exception:chained | ExceptionCollector |
| `/test/scenarios/multi` | multi:logs-and-events | LogCollector, EventCollector, TimelineCollector |
| `/test/scenarios/logs-heavy` | logs:heavy | LogCollector |
| `/test/scenarios/http-client` | http-client:basic | HttpClientCollector |
| `/test/scenarios/filesystem` | filesystem:basic | FilesystemStreamCollector |

## Running Scenarios

Two ways to run: CLI command or PHPUnit E2E tests. Both require a running playground server.

### CLI Command

```bash
debug:scenarios http://localhost:8080              # All scenarios
debug:scenarios http://localhost:8080 --tag=core   # By tag
debug:scenarios http://localhost:8080 -s logs:basic # Single scenario
debug:scenarios http://localhost:8080 --list       # List without running
debug:scenarios http://localhost:8080 -v           # Verbose assertions
```

### PHPUnit E2E Tests

```bash
# Run all scenario tests against a playground
PLAYGROUND_URL=http://127.0.0.1:8102 php vendor/bin/phpunit --testsuite Scenarios

# Run specific group
PLAYGROUND_URL=http://127.0.0.1:8102 php vendor/bin/phpunit --testsuite Scenarios --group core

# Available groups: core, web, error, advanced, api
```

### Makefile Targets

```bash
make scenarios                  # CLI scenarios against all playgrounds
make test-scenarios             # PHPUnit E2E against all playgrounds
make test-scenarios-symfony     # PHPUnit E2E against Symfony only
```
