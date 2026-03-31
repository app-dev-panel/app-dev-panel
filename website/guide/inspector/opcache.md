---
title: OPcache Inspector
---

# OPcache Inspector

View PHP OPcache status and configuration.

![OPcache Inspector](/images/inspector/opcache.png)

## What It Shows

| Section | Description |
|---------|-------------|
| Status | OPcache enabled/disabled, memory usage, hit rate |
| Configuration | OPcache INI directives and their values |
| Statistics | Cache hits, misses, restarts, cached scripts count |

## API Endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET | `/inspect/api/opcache` | OPcache status and configuration |

::: info
Returns HTTP 422 if OPcache is not enabled on the server.
:::
