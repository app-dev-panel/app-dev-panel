# Editor Integration — Status & Remaining Work

Status: 2026-03-30.

## Implemented

Core editor integration is complete:

- **FileLink component** (`sdk/src/Component/FileLink.tsx`) — renders internal file explorer link + "Open in Editor" icon button
- **StackTrace component** (`sdk/src/Component/StackTrace.tsx`) — parses PHP stack traces, makes frames clickable with editor links
- **useEditorUrl hook** (`sdk/src/Helper/useEditorUrl.ts`) — Redux-backed editor URL builder with presets for PhpStorm, VS Code, Cursor, Sublime, Zed, etc.
- **Settings UI** — editor selector with presets + custom URL template

Supported editors: PhpStorm, VS Code, VS Code Insiders, Cursor, Sublime Text, Zed, Nova, Netbeans, custom URL template.

## Remaining Work

| Feature | Effort | Priority |
|---------|--------|----------|
| HTTP callback for Docker/WSL/remote (POST to local editor plugin) | Medium | P2 |
| Path mapping for remote-to-local paths (`{"/app": "/Users/me/project"}`) | Medium | P2 |
| Source map support for frontend errors | High | P3 |
