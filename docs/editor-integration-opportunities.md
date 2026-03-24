# Editor Integration Opportunities

## Current State

All file/class references in ADP link to the **internal File Explorer** (`/inspector/files`). There is **no existing editor/IDE integration** — no protocol handlers, no "Open in Editor" buttons, no configuration for external editors.

## Integration 1: Open in Editor via URL Protocols

### Concept

Most IDEs support custom URL protocols to open files at specific lines:

| Editor | Protocol | Format |
|--------|----------|--------|
| PhpStorm | `phpstorm://` | `phpstorm://open?file={file}&line={line}` |
| VS Code | `vscode://` | `vscode://file/{file}:{line}` |
| VS Code Insiders | `vscode-insiders://` | `vscode-insiders://file/{file}:{line}` |
| Cursor | `cursor://` | `cursor://file/{file}:{line}` |
| Sublime Text | `subl://` | `subl://open?url=file://{file}&line={line}` |
| Atom | `atom://` | `atom://core/open/file?filename={file}&line={line}` |
| TextMate | `txmt://` | `txmt://open?url=file://{file}&line={line}` |
| Emacs | `emacs://` | `emacs://open?url=file://{file}&line={line}` |
| Nova | `nova://` | `nova://open?path={file}&line={line}` |
| Netbeans | `netbeans://` | `netbeans://open?file={file}&line={line}` |
| Zed | `zed://` | `zed://file/{file}:{line}` |

### Implementation Plan

1. **Add `editorProtocol` setting** to `ApplicationContext` (Redux slice) with presets + custom URL template
2. **Create `buildEditorUrl(file, line)` helper** in `sdk/Helper/` that generates the protocol URL from the template
3. **Add "Open in Editor" icon button** next to every file link across all panels
4. **Add editor selector** in Settings page (dropdown with presets + custom template input)

### All Locations Requiring Changes (26 file links across 16 components)

#### Debug Module — Panels

| Component | File | Links | What's Shown |
|-----------|------|-------|-------------|
| **ExceptionPanel** | `panel/src/Module/Debug/Component/Panel/ExceptionPanel.tsx:124,134` | `?class={class}`, `?path={file}#L{line}` | Exception class + source location |
| **ExceptionPreview** | `panel/src/Module/Debug/Component/Exception/ExceptionPreview.tsx:64,74` | `?class={class}`, `?path={file}#L{line}` | Same as above (compact view) |
| **LogPanel** | `panel/src/Module/Debug/Component/Panel/LogPanel.tsx:207` | `?path={file}#L{line}` | Log call site |
| **EventPanel** | `panel/src/Module/Debug/Component/Panel/EventPanel.tsx:244` | `?path={file}#L{line}` | Event dispatch site |
| **HttpClientPanel** | `panel/src/Module/Debug/Component/Panel/HttpClientPanel.tsx:320` | `?path={file}#L{line}` | HTTP request call site |
| **VarDumperPanel** | `panel/src/Module/Debug/Component/Panel/VarDumperPanel.tsx:63` | `?path={file}#L{line}` | dump() call site |
| **FilesystemPanel** | `panel/src/Module/Debug/Component/Panel/FilesystemPanel.tsx:77` | `?path={file}` | File operation path |
| **TimelineContentWrapper** | `panel/src/Module/Debug/Component/Timeline/TimelineContentWrapper.tsx:33` | `?path={file}` | Timeline entry file |

#### Inspector Module — Pages

| Component | File | Links | What's Shown |
|-----------|------|-------|-------------|
| **FileExplorerPage** | `panel/src/Module/Inspector/Pages/FileExplorerPage.tsx` | Renders file content directly | Full file viewer |
| **RoutesPage** | `panel/src/Module/Inspector/Pages/RoutesPage.tsx:181,201,241,494,502` | `?class={class}&method={method}` | Route handler + middleware classes |
| **EventsPage** | `panel/src/Module/Inspector/Pages/EventsPage.tsx:134,152,169` | `?class={class}`, `?class={class}&method={method}` | Event listener classes |
| **ContainerEntryPage** | `panel/src/Module/Inspector/Pages/ContainerEntryPage.tsx:25` | `?path={file}` | Service definition file |
| **TestsPage** | `panel/src/Module/Inspector/Pages/TestsPage.tsx:38` | `?path={file}#L{line}` | Test file + line |
| **AnalysePage** | `panel/src/Module/Inspector/Pages/AnalysePage.tsx:47` | `?path={file}#L{from}-{to}` | Static analysis issue location |

#### Shared Infrastructure

| Component | File | Purpose |
|-----------|------|---------|
| **filePathParser.ts** | `sdk/src/Helper/filePathParser.ts` | Parses file paths, extracts line numbers, builds `#L{line}` anchors |
| **CodeHighlight** | `sdk/src/Component/CodeHighlight.tsx` | Renders syntax-highlighted code with line number anchors |
| **ApplicationContext** | `sdk/src/API/Application/ApplicationContext.tsx` | Redux state — will hold editor config |

### URL Patterns Used

All links follow one of three patterns:
```
/inspector/files?path={filePath}                    # File only
/inspector/files?path={filePath}#L{line}            # File + line
/inspector/files?class={className}&method={method}  # Class + method resolution
```

### Approach: Reusable `FileLink` Component

Create a single `<FileLink>` component in SDK that:
- Renders internal link to File Explorer (current behavior)
- Renders an additional "Open in Editor" icon button when editor is configured
- Accepts `file`, `line`, `className`, `methodName` props
- Replaces raw `href` strings in all 16 components above

This centralizes file linking logic and makes future changes trivial.

---

## Integration 2: Click-to-Open on Code Highlight Lines

### Concept

In the `CodeHighlight` component, make line numbers clickable to open that specific line in the editor. Currently line numbers are just visual. Clicking a line number would trigger the editor protocol.

### Location

- `sdk/src/Component/CodeHighlight.tsx` — Add `onLineClick` handler or auto-generate editor URLs for each line when editor is configured
- Affects: ExceptionPanel (code preview), FileExplorerPage (full file view)

---

## Integration 3: Stack Trace Line Linking

### Concept

Exception stack traces are currently rendered as plain text (`traceAsString`). Parse them to make each frame clickable — link to both File Explorer and external editor.

### Locations

- `panel/src/Module/Debug/Component/Panel/ExceptionPanel.tsx:183` — `TraceBlock` renders raw text
- Stack trace format: `#0 /path/to/file.php(42): ClassName->method()`

### Implementation

Create a `<StackTrace>` component that parses the trace string, extracts `file:line` from each frame, and renders them as clickable links.

---

## Integration 4: Copy File Path / Copy as Editor URL

### Concept

Add context menu or action buttons to copy:
- Absolute file path to clipboard
- Editor protocol URL to clipboard
- `file:line` reference

### Locations

- FileExplorerPage header (file path display, line 142)
- Every file link across panels (same 16 components from Integration 1)

---

## Integration 5: Editor Integration via HTTP Callback (Remote Open)

### Concept

For environments where URL protocols don't work (remote servers, Docker, WSL), provide an HTTP callback approach. The panel sends a POST request to a local editor plugin/server that opens the file.

Existing solutions:
- **Laravel Ignition** uses this pattern with a local server
- **PhpStorm REST API** (`http://localhost:63342/api/file/{file}:{line}`)
- Custom callback URL configurable in settings

### Implementation

Add `editorCallbackUrl` to settings as an alternative to protocol URLs. When set, clicking "Open in Editor" sends `POST {callbackUrl}` with `{file, line}` payload.

---

## Integration 6: Source Map Support (Frontend Errors)

### Concept

When ADP collects frontend exceptions, file paths point to bundled/minified code. Integrate source map resolution to show original source locations and link to the correct file in the editor.

### Scope

Future consideration — requires source map upload or inline source map support in collectors.

---

## Implementation Priority

| # | Integration | Effort | Impact | Priority |
|---|------------|--------|--------|----------|
| 1 | URL Protocol (Open in Editor) | Medium | High | **P0** — Core feature |
| 2 | Code Highlight line click | Low | Medium | **P1** — Quick win |
| 3 | Stack Trace linking | Medium | High | **P1** — Major UX improvement |
| 4 | Copy path / Copy URL | Low | Medium | **P2** — Convenience |
| 5 | HTTP Callback (remote) | Medium | Medium | **P2** — Needed for Docker/WSL |
| 6 | Source Map Support | High | Low | **P3** — Future |

## Settings Schema

```typescript
type EditorConfig = {
    editor: 'none' | 'phpstorm' | 'vscode' | 'vscode-insiders' | 'cursor' | 'sublime' | 'zed' | 'custom';
    customUrlTemplate: string; // e.g. "myeditor://open?file={file}&line={line}"
    pathMapping: Record<string, string>; // Remote-to-local path mapping, e.g. {"/app": "/Users/me/project"}
};
```

`pathMapping` is essential for Docker/Vagrant/remote setups where server paths differ from local paths.

## Files to Create/Modify

### New Files
- `sdk/src/Helper/editorUrl.ts` — URL builder with presets and template interpolation
- `sdk/src/Component/FileLink.tsx` — Reusable file link component (internal + editor)
- `sdk/src/Component/StackTrace.tsx` — Parsed stack trace with clickable frames

### Modified Files
- `sdk/src/API/Application/ApplicationContext.tsx` — Add `editorConfig` to state
- `sdk/src/Helper/filePathParser.ts` — Add path mapping support
- All 14 panel/page components listed above — Replace raw `href` with `<FileLink>`
- `panel/src/Application/Component/Settings/` — Add Editor configuration UI
- `sdk/src/Component/CodeHighlight.tsx` — Add clickable line numbers
