---
description: "Установка и настройка ADP для Spiral Framework 3.x. Регистрация Bootloader, PSR-15 middleware-конвейер, фикстурные эндпоинты."
---

# Адаптер Spiral

Адаптер Spiral связывает ADP Kernel и API со Spiral Framework 3.14+ через Bootloader.
Spiral нативно работает с PSR-7/PSR-15, поэтому адаптер — самый тонкий из четырёх:
никаких мостов HttpFoundation/Illuminate Request, никаких compiler pass.

## Установка

```bash
composer require app-dev-panel/adapter-spiral --dev
```

::: info Пакет
<pkg>app-dev-panel/adapter-spiral</pkg>
:::

## Конфигурация

Зарегистрируйте Bootloader в `Kernel` приложения:

```php
final class Kernel extends \Spiral\Framework\Kernel
{
    public function defineBootloaders(): array
    {
        return [
            // ... ваши Bootloader'ы ...
            \AppDevPanel\Adapter\Spiral\Bootloader\AppDevPanelBootloader::class,
        ];
    }
}
```

Подключите два PSR-15 middleware в HTTP-конвейер (наружные, до CSRF / sessions):

```php
// app/config/http.php
return [
    'middleware' => [
        \AppDevPanel\Adapter\Spiral\Middleware\AdpApiMiddleware::class,
        \AppDevPanel\Adapter\Spiral\Middleware\DebugMiddleware::class,
        // ... ваши middleware ...
    ],
];
```

`AdpApiMiddleware` должен идти перед `DebugMiddleware`, чтобы запросы к собственным
маршрутам ADP (`/debug/*`, `/inspect/api/*`) сразу уходили в `ApiApplication` и не
попадали под трассировку Debugger.

## Переменные окружения

| Переменная | Назначение | По умолчанию |
|------------|------------|--------------|
| `APP_DEV_PANEL_STORAGE_PATH` | Каталог файлового хранилища debug-записей | `sys_get_temp_dir()/app-dev-panel` |
| `APP_DEV_PANEL_STATIC_URL` | Базовый URL статики панели (можно переопределить GitHub Pages CDN) | `https://app-dev-panel.github.io/app-dev-panel` |

## Коллекторы

Bootloader регистрирует все framework-agnostic коллекторы Kernel плюс несколько таких,
которым нужен только вызов `collect()` из вашего кода:

`LogCollector`, `EventCollector`, `ExceptionCollector`, `HttpClientCollector`,
`VarDumperCollector`, `TimelineCollector`, `RequestCollector`, `WebAppInfoCollector`,
`FilesystemStreamCollector`, `CacheCollector`, `RouterCollector`, `ValidatorCollector`,
`TranslatorCollector`, `TemplateCollector`, `MailerCollector`, `QueueCollector`.

Ваши PSR-сервисы автоматически декорируются на этапе `boot()` Bootloader'а:
`LoggerInterface` → `LoggerInterfaceProxy` (питает `LogCollector`),
`EventDispatcherInterface` → `EventDispatcherInterfaceProxy` (питает `EventCollector`),
`ClientInterface` (PSR-18) → `HttpClientInterfaceProxy` (питает `HttpClientCollector`).

## Архитектура

Адаптер намеренно маленький — три класса:

- `AppDevPanelBootloader` — Spiral `Bootloader`, регистрирует ADP-сервисы как singleton'ы
  и декорирует PSR-сервисы в `boot()`.
- `DebugMiddleware` — PSR-15 middleware, оборачивает остальной конвейер в
  `Debugger::startup()` / `Debugger::shutdown()`. При исключении возвращает
  синтетический `500`-ответ с заголовком `X-Debug-Id`, чтобы запись всё равно
  отображалась в панели.
- `AdpApiMiddleware` — PSR-15 middleware, перехватывает `/debug`, `/debug/api/*`,
  `/inspect/api/*` и передаёт их в `ApiApplication`.

## Сравнение с другими адаптерами

| Аспект | Symfony / Laravel | Spiral |
|--------|-------------------|--------|
| Регистрация | Bundle / ServiceProvider | Bootloader |
| HTTP типы | HttpFoundation → PSR-7 конвертация | **PSR-7 нативно** — без конвертации |
| Хук в lifecycle | События ядра (`kernel.request` / `kernel.terminate`) | PSR-15 middleware в HTTP-конвейере |
| Маршруты ADP | Маршруты фреймворка → catch-all контроллер | PSR-15 middleware перехватывает пути ADP |

## Playground

Эталонный Spiral-плейграунд находится в `playground/spiral-app/`. Запускается на
встроенном PHP-сервере (порт `8105`):

```bash
make serve-spiral          # http://127.0.0.1:8105/
make fixtures-spiral       # CLI-фикстуры
make test-fixtures-spiral  # PHPUnit E2E
```
