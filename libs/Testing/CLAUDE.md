# Testing Module

Central test scenario definitions and runner for verifying ADP collectors across all adapters and playgrounds.

## Package

- Composer: `app-dev-panel/testing`
- Namespace: `AppDevPanel\Testing\`
- PHP: 8.4+
- Dependencies: `guzzlehttp/guzzle`, `guzzlehttp/psr7`

## Architecture

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

## CLI Usage

```bash
# List all scenarios
debug:scenarios --list http://localhost:8080

# Run all scenarios against a playground
debug:scenarios http://localhost:8080

# Run specific tag group
debug:scenarios http://localhost:8080 --tag=core

# Run single scenario
debug:scenarios http://localhost:8080 --scenario=logs:basic

# Verbose output (show all assertions)
debug:scenarios http://localhost:8080 -v
```
