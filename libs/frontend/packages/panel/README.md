# @app-dev-panel/panel

Main SPA for the Application Development Panel — debug data viewer, live application inspector, code generation, and OpenAPI browser.

## Installation

```shell
npm i @app-dev-panel/panel
```

## Modules

- **Debug** — view collected runtime data
    - Repeat requests with one click
    - Collectors: Log, Event, Service, Validator, Queue, WebAppInfo, Request, Router, Middleware, Asset, WebView, Database, Cache, Mailer, Exception, HttpClient, Timeline, VarDumper, Filesystem, HttpStream, Environment
- **Inspector** — live application introspection (20+ pages)
    - Routes, Parameters, Configuration, Container, File Explorer, Translations, Commands, Database, Git, PHP Info, Composer, Cache, OPcache, Events
- **GenCode** — code generation with preview and diff
- **OpenAPI** — Swagger UI for the debug API

## Usage

### As a Standalone Application

Build the app and serve it with any web server (nginx, Apache, Node, PHP built-in server). Configure the backend URL in Settings.

### As a PWA

Open [https://app-dev-panel.github.io/app-dev-panel/demo/](https://app-dev-panel.github.io/app-dev-panel/demo/)

1. **Online mode** — set the backend URL and use directly in the browser
2. **Installed app** — click "Install" in the address bar, run from Applications

Both options work on mobile devices.

## Development

```shell
npm install
npm start
```

The panel serves on `http://localhost:3000`.

## Screenshots

<details>
  <summary>Debug</summary>
  <img src="docs/debug.collector.event.png" alt="Event Collector"/>
  <img src="docs/debug.collector.middleware.png" alt="Middleware Collector"/>
  <img src="docs/debug.collector.request.png" alt="Request Collector"/>
  <img src="docs/debug.collector.response.png" alt="Response Details"/>
  <img src="docs/debug.collector.service.png" alt="Service Collector"/>
  <img src="docs/debug.logger.png" alt="Log Viewer"/>
</details>

<details>
  <summary>Inspector</summary>
  <img src="docs/inspector.composer.png" alt="Composer Inspector"/>
  <img src="docs/inspector.definitions.png" alt="Container Definitions"/>
  <img src="docs/inspector.events.png" alt="Event Listeners"/>
  <img src="docs/inspector.files.png" alt="File Explorer"/>
  <img src="docs/inspector.git.png" alt="Git Inspector"/>
  <img src="docs/inspector.parameters.png" alt="Parameters"/>
  <img src="docs/inspector.php.png" alt="PHP Info"/>
  <img src="docs/inspector.router.png" alt="Route Inspector"/>
  <img src="docs/inspector.router2.png" alt="Route Details"/>
</details>

<details>
  <summary>Frames</summary>
  <img src="docs/frames.png" alt="Frames"/>
</details>
