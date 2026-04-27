---
title: OpenAPI Viewer
description: "Built-in Swagger UI for viewing and testing your application's OpenAPI specifications directly in the ADP panel."
---

# OpenAPI Viewer

The OpenAPI module embeds [Swagger UI](https://swagger.io/tools/swagger-ui/) directly into the ADP panel. Point it at any OpenAPI/Swagger JSON endpoint and browse your API documentation without leaving the debug panel.

## How It Works

The viewer fetches an OpenAPI 3.x (or Swagger 2.x) JSON spec from a URL you provide and renders it using Swagger UI. You can add multiple API specs — each appears as a separate tab.

```
┌──────────────────────────────────────────────────┐
│  Open API                                        │
│  API documentation viewer                        │
│                                                  │
│  ┌─────────────────┐  ┌───┐                      │
│  │ MY APP API      │  │ ⚙ │  ← Settings button  │
│  └─────────────────┘  └───┘                      │
│                                                  │
│  ┌──────────────────────────────────────────────┐│
│  │  Swagger UI                                  ││
│  │  GET  /api        API index            ▼     ││
│  │  GET  /api/users  List users           ▼     ││
│  │  POST /api/users  Create user          ▼     ││
│  └──────────────────────────────────────────────┘│
└──────────────────────────────────────────────────┘
```

## Adding an API Spec

1. Navigate to **Open API** in the sidebar
2. Click the **gear icon** (⚙) in the top-right corner
3. Enter the full URL to your OpenAPI JSON spec (e.g. `http://127.0.0.1:8103/api/openapi.json`)
4. Click the **checkmark** to confirm
5. Close the dialog — Swagger UI loads automatically

Entries are persisted to a [committable project config file](/guide/project-config) (`config/adp/project.json`) on the backend, with a `localStorage` fallback for offline use. Commit that file and your teammates pick up the same Swagger entries after `git pull`.

You can add multiple entries. Each one appears as a separate tab.

## CORS Requirements

The OpenAPI JSON endpoint must return CORS headers if the ADP panel is served from a different origin. At minimum:

```
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, OPTIONS
Access-Control-Allow-Headers: Content-Type
```

Without these headers, the browser blocks the fetch and Swagger UI shows a "Failed to load API definition" error.

## Generating OpenAPI Specs

ADP playground applications include a built-in `/api/openapi.json` endpoint that generates the OpenAPI spec from PHP attributes using [swagger-php](https://zircote.github.io/swagger-php/).

### Adding OpenAPI Attributes

Annotate your API controllers with `OpenApi\Attributes`:

```php
use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'My Application API',
    description: 'API documentation for my app.',
)]
final class ApiController
{
    #[OA\Get(
        path: '/api/users',
        summary: 'List users',
        tags: ['Users'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of users',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'users',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer'),
                                    new OA\Property(property: 'name', type: 'string'),
                                ],
                            ),
                        ),
                    ],
                ),
            ),
        ],
    )]
    public function list(): JsonResponse { /* ... */ }
}
```

### Creating the Spec Endpoint

Create a controller that scans your source directory and returns the generated spec:

:::tabs key:framework
== Symfony
```php
use OpenApi\Generator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class OpenApiController
{
    #[Route('/api/openapi.json', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $openapi = Generator::scan([dirname(__DIR__)]);

        $response = new JsonResponse($openapi->toJson(), json: true);
        $response->headers->set('Access-Control-Allow-Origin', '*');

        return $response;
    }
}
```
== Laravel
```php
use Illuminate\Http\JsonResponse;
use OpenApi\Generator;

final class OpenApiController
{
    public function __invoke(): JsonResponse
    {
        $openapi = Generator::scan([app_path()]);

        return (new JsonResponse($openapi->toJson(), json: true))
            ->header('Access-Control-Allow-Origin', '*');
    }
}
```
== Yii 3
```php
use OpenApi\Generator;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

final readonly class OpenApiAction
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $openapi = Generator::scan([dirname(__DIR__, 2)]);
        $spec = json_decode($openapi->toJson(), true, 512, JSON_THROW_ON_ERROR);

        return $this->responseFactory->createResponse($spec)
            ->withHeader('Access-Control-Allow-Origin', '*');
    }
}
```
== Yii 2
```php
use OpenApi\Generator;
use yii\web\Controller;
use yii\web\Response;

final class OpenApiController extends Controller
{
    public function actionIndex(): array
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        \Yii::$app->response->headers->set('Access-Control-Allow-Origin', '*');

        $openapi = Generator::scan([dirname(__DIR__)]);

        return json_decode($openapi->toJson(), true, 512, JSON_THROW_ON_ERROR);
    }
}
```
:::

### Installation

Add `zircote/swagger-php` to your project:

```bash
composer require zircote/swagger-php
```

## Playground URLs

Each ADP playground application includes an OpenAPI endpoint out of the box:

| Playground | Port | OpenAPI URL |
|------------|------|-------------|
| Yii 3 | 8101 | `http://127.0.0.1:8101/api/openapi.json` |
| Symfony | 8102 | `http://127.0.0.1:8102/api/openapi.json` |
| Yii 2 | 8103 | `http://127.0.0.1:8103/api/openapi.json` |
| Laravel | 8104 | `http://127.0.0.1:8104/api/openapi.json` |

## Technical Details

- **Frontend module**: `libs/frontend/packages/panel/src/Module/OpenApi/`
- **State**: Redux slice `store.openApi`, mirrored to `localStorage` for offline use and synced to `config/adp/project.json` on the backend (see [Project Config](/guide/project-config))
- **Swagger UI**: Rendered via `swagger-ui-react` package
- **Dark mode**: Supported — CSS overrides applied based on the current theme
