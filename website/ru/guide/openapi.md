---
title: Просмотр OpenAPI
description: "Встроенный Swagger UI для просмотра и тестирования OpenAPI-спецификаций вашего приложения непосредственно в панели ADP."
---

# Просмотр OpenAPI

Модуль OpenAPI встраивает [Swagger UI](https://swagger.io/tools/swagger-ui/) непосредственно в панель ADP. Укажите любой JSON-эндпоинт OpenAPI/Swagger и просматривайте документацию вашего API, не покидая панель отладки.

## Как это работает

Просмотрщик загружает JSON-спецификацию OpenAPI 3.x (или Swagger 2.x) по указанному вами URL и отображает её с помощью Swagger UI. Можно добавить несколько спецификаций API — каждая появится в отдельной вкладке.

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

## Добавление спецификации API

1. Перейдите в раздел **Open API** в боковой панели
2. Нажмите **иконку шестерёнки** (⚙) в правом верхнем углу
3. Введите полный URL JSON-спецификации OpenAPI (например, `http://127.0.0.1:8103/api/openapi.json`)
4. Нажмите **галочку** для подтверждения
5. Закройте диалог — Swagger UI загрузится автоматически

Записи сохраняются в [коммитируемый файл конфигурации проекта](/ru/guide/project-config) (`config/adp/project.json`) на бэкенде, с резервным хранением в `localStorage` для офлайн-использования. Закоммитьте этот файл, и ваши коллеги получат те же записи Swagger после `git pull`.

Можно добавить несколько записей. Каждая появится в отдельной вкладке.

## Требования CORS

JSON-эндпоинт OpenAPI должен возвращать CORS-заголовки, если панель ADP отдаётся с другого origin. Как минимум:

```
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, OPTIONS
Access-Control-Allow-Headers: Content-Type
```

Без этих заголовков браузер блокирует запрос, и Swagger UI показывает ошибку «Failed to load API definition».

## Генерация OpenAPI-спецификаций

Playground-приложения ADP включают встроенный эндпоинт `/api/openapi.json`, который генерирует OpenAPI-спецификацию из PHP-атрибутов с помощью [swagger-php](https://zircote.github.io/swagger-php/).

### Добавление OpenAPI-атрибутов

Аннотируйте контроллеры API атрибутами `OpenApi\Attributes`:

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

### Создание эндпоинта спецификации

Создайте контроллер, который сканирует каталог с исходным кодом и возвращает сгенерированную спецификацию:

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

### Установка

Добавьте `zircote/swagger-php` в ваш проект:

```bash
composer require zircote/swagger-php
```

## URL playground-приложений

Каждое playground-приложение ADP включает OpenAPI-эндпоинт «из коробки»:

| Playground | Порт | URL OpenAPI |
|------------|------|-------------|
| Yii 3 | 8101 | `http://127.0.0.1:8101/api/openapi.json` |
| Symfony | 8102 | `http://127.0.0.1:8102/api/openapi.json` |
| Yii 2 | 8103 | `http://127.0.0.1:8103/api/openapi.json` |
| Laravel | 8104 | `http://127.0.0.1:8104/api/openapi.json` |

## Технические детали

- **Фронтенд-модуль**: `libs/frontend/packages/panel/src/Module/OpenApi/`
- **Состояние**: Redux slice `store.openApi`, дублируется в `localStorage` для офлайн-использования и синхронизируется с `config/adp/project.json` на бэкенде (см. [Конфигурация проекта](/ru/guide/project-config))
- **Swagger UI**: отображается через пакет `swagger-ui-react`
- **Тёмная тема**: поддерживается — CSS-переопределения применяются в зависимости от текущей темы
