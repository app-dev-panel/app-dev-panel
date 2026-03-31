---
title: Configuration Inspector
---

# Configuration Inspector

Inspect your application's DI container configuration, service definitions, and parameters.

![Configuration Inspector](/images/inspector/config.png)

## Tabs

### Parameters

View all application parameters and their values. Includes framework-specific parameters (e.g., `kernel.project_dir` for Symfony, `app.debug` for Laravel).

### Definitions

Browse all service class definitions registered in the DI container. See which classes and interfaces are available for dependency injection.

### Container

Inspect individual container services. View how a service is configured, its dependencies, and runtime state.

## Container Entry Viewer

Click any service to see its full object dump — properties, dependencies, and configuration values resolved at runtime.

## API Endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET | `/inspect/api/params` | Application parameters |
| GET | `/inspect/api/config` | DI configuration by group |
| GET | `/inspect/api/classes` | All declared classes/interfaces |
| GET | `/inspect/api/object?classname=App\Service\UserService` | Instantiate and dump a container object |

## Adapter Support

Each adapter maps its framework's DI container to the inspector interface:
- **Symfony**: Introspects `service_container` and compiler pass data
- **Laravel**: Maps service bindings and resolved instances
- **Yii 3**: Maps container definitions
- **Yii 2**: Maps component and service locator config
