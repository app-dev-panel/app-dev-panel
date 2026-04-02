---
title: Editor Integration
description: "Planned ADP editor integration for VS Code, PhpStorm, and other IDEs. Open-file links and inline debugging."
---

# Editor Integration

::: warning PLANNED FEATURE
Editor integration is planned but not yet implemented. This page describes the design and what to expect. Contributions are welcome — see [Contributing](/guide/contributing).
:::

ADP plans to support "Open in Editor" functionality, allowing you to click file references in the debug panel and jump directly to the source code in your IDE.

## Supported Editors

The following editors support custom URL protocols:

| Editor | Protocol | URL Format |
|--------|----------|-----------|
| PhpStorm | `phpstorm://` | `phpstorm://open?file={file}&line={line}` |
| VS Code | `vscode://` | `vscode://file/{file}:{line}` |
| VS Code Insiders | `vscode-insiders://` | `vscode-insiders://file/{file}:{line}` |
| Cursor | `cursor://` | `cursor://file/{file}:{line}` |
| Sublime Text | `subl://` | `subl://open?url=file://{file}&line={line}` |
| Zed | `zed://` | `zed://file/{file}:{line}` |
| Nova | `nova://` | `nova://open?path={file}&line={line}` |
| Netbeans | `netbeans://` | `netbeans://open?file={file}&line={line}` |

## Planned Features

### 1. Open in Editor (URL Protocol)

An "Open in Editor" button next to every file reference across all panels — exceptions, logs, events, HTTP client calls, var dumps, stack traces, routes, and more.

Settings will include editor preset selection and a custom URL template option.

### 2. Clickable Code Line Numbers

In the code viewer (`CodeHighlight` component), line numbers will become clickable to open that specific line in your editor.

### 3. Stack Trace Linking

Exception stack traces (currently plain text) will be parsed so each frame links to the file and line in both the ADP File Explorer and your editor.

### 4. Copy Path / Copy Editor URL

Context actions to copy:
- Absolute file path
- Editor protocol URL
- `file:line` reference

### 5. HTTP Callback (Remote/Docker)

For environments where URL protocols don't work (Docker, WSL, remote servers), an HTTP callback approach. The panel sends a POST request to a local editor plugin or the PhpStorm REST API (`http://localhost:63342/api/file/{file}:{line}`).

### 6. Path Mapping

For Docker/Vagrant/remote setups where server paths differ from local paths:

```typescript
type EditorConfig = {
    editor: 'phpstorm' | 'vscode' | 'cursor' | 'custom' | ...;
    customUrlTemplate: string;
    pathMapping: Record<string, string>;
    // e.g. {"/app": "/Users/me/project"}
};
```

## Priority

| Feature | Effort | Impact |
|---------|--------|--------|
| URL Protocol (Open in Editor) | Medium | High |
| Code line click | Low | Medium |
| Stack Trace linking | Medium | High |
| Copy path / Copy URL | Low | Medium |
| HTTP Callback (remote) | Medium | Medium |
