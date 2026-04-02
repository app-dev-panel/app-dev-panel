---
title: Code Coverage
description: "ADP Code Coverage inspector runs PCOV/Xdebug coverage and displays per-file line coverage results."
---

# Code Coverage

Collect and view PHP code coverage data in real-time.

![Code Coverage](/images/inspector/coverage.png)

## What It Shows

| Field | Description |
|-------|-------------|
| Driver | Coverage driver in use (`pcov` or `xdebug`) |
| Total files | Number of files with coverage data |
| Covered lines | Lines executed during the request |
| Executable lines | Total lines that can be executed |
| Percentage | Overall coverage percentage |

## Per-File Coverage

Click a file to see line-by-line coverage highlighting — which lines were executed (green) and which were missed (red).

## API Endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET | `/inspect/api/coverage` | Collect coverage data with per-file stats |
| GET | `/inspect/api/coverage/file?path=/src/Service.php` | Read source file for coverage display |

## Requirements

Requires one of:
- **PCOV** extension (recommended, lightweight)
- **Xdebug** extension with coverage mode enabled

::: warning
Code coverage collection adds overhead. Enable only during development/testing.
:::
