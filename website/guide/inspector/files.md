---
title: File Explorer
---

# File Explorer

Browse your project's file system, view source code, and navigate to class definitions.

![File Explorer](/images/inspector/files.png)

## What It Shows

| Feature | Description |
|---------|-------------|
| Directory listing | Navigate directories with file metadata |
| File viewer | Read source files with syntax highlighting |
| Class resolution | Jump to any PHP class by FQCN |
| Method navigation | Jump to specific method start/end lines |

## File Metadata

Each file entry shows:
- **Name** and **extension**
- **Size** (bytes)
- **Permissions** (octal)
- **Owner/group** (username or UID)
- **Last modified** time

## Class & Method Resolution

Provide a fully qualified class name (e.g., `App\Controller\UserController`) and optionally a method name to jump directly to the source file at the correct line.

## API Endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET | `/inspect/api/files?path=/src` | List directory contents or read file |
| GET | `/inspect/api/files?class=App\Controller\UserController` | Resolve class to file |
| GET | `/inspect/api/files?class=App\Controller\UserController&method=index` | Resolve class method with line range |

## IDE Integration

File paths are mapped via `PathMapperInterface` for IDE integration. Click a file path to open it in your local IDE (VS Code, PhpStorm, etc.) if configured.

::: info
File access is restricted to the project root directory. Attempting to navigate outside the root returns a 403 error.
:::
