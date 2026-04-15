---
title: Editor Integration
description: "ADP editor integration: Open in Editor links for VS Code, PhpStorm, Cursor, and other IDEs. Path mapping for Docker/WSL/remote setups."
---

# Editor Integration

ADP renders an "Open in Editor" button next to every file reference (exceptions, logs, events, stack traces, routes). Clicking it launches your IDE at the right file and line via a custom URL protocol.

## Configuration

Open the Settings dialog (top-bar menu → **Settings**) and pick an editor in the **Editor Integration** section. The setting is per-browser and persists in `localStorage`.

## Supported Editors

| Editor | URL Format |
|--------|-----------|
| PhpStorm | `phpstorm://open?file={file}&line={line}` |
| IntelliJ IDEA | `idea://open?file={file}&line={line}` |
| WebStorm | `webstorm://open?file={file}&line={line}` |
| GoLand | `goland://open?file={file}&line={line}` |
| PyCharm | `pycharm://open?file={file}&line={line}` |
| RubyMine | `rubymine://open?file={file}&line={line}` |
| Rider | `rider://open?file={file}&line={line}` |
| CLion | `clion://open?file={file}&line={line}` |
| VS Code | `vscode://file/{file}:{line}` |
| VS Code Insiders | `vscode-insiders://file/{file}:{line}` |
| Cursor | `cursor://file/{file}:{line}` |
| Sublime Text | `subl://open?url=file://{file}&line={line}` |
| Zed | `zed://file/{file}:{line}` |
| Custom | user-supplied template with `{file}` / `{line}` placeholders |

## Path Mapping (Docker / WSL / Remote)

When the application runs inside a container or remote host, file paths reported to ADP (e.g. `/app/src/Foo.php`) don't match what your local IDE expects (e.g. `/Users/me/project/src/Foo.php`). Path mapping rewrites those paths before the editor URL is built.

In the Settings dialog, under the editor selector, add **Remote → Local** mapping rows:

| Remote | Local |
|--------|-------|
| `/app` | `/Users/me/project` |
| `/var/www` | `/home/user/site` |

Rules:

- The first matching remote prefix wins.
- Empty remote keys are ignored.
- Duplicate remote keys are flagged in the UI; the last value wins.
- Mappings persist in `localStorage` alongside the editor preset.

## Where Editor Links Appear

| Component | Location |
|-----------|----------|
| `FileLink` | Any single-file reference (logs, events, var dumps, route source) |
| `StackTrace` | Each frame in exception stack traces |

Clicking the file path opens the ADP File Explorer in-app; clicking the editor icon (next to the path) launches your IDE.

## Planned

| Feature | Why |
|---------|-----|
| HTTP callback transport | URL protocols don't work in some browsers/OSes; POST to a local editor plugin (e.g. PhpStorm `http://localhost:63342/api/file/...`) is more reliable |
| Source map support | Frontend stack traces currently point to bundled files — resolve back to original sources |
