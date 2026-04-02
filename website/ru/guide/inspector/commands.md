---
title: Команды
---

# Команды

Запуск команд приложения прямо из панели отладки — тесты, статический анализ и скрипты Composer.

![Команды](/images/inspector/commands.png)

## Встроенные команды

Встроенные команды обнаруживаются автоматически. Если инструмент установлен, команда появляется автоматически — настройка не требуется.

### Статический анализ

| Команда | Инструмент | Автоопределение |
|---------|------------|-----------------|
| `analyse/psalm` | [Psalm](https://psalm.dev/) | `vimeo/psalm` в Composer |
| `analyse/phpstan` | [PHPStan](https://phpstan.org/) | `phpstan/phpstan` в Composer |
| `analyse/mago` | [Mago](https://mago.carthage.software/) | `carthage-software/mago` в Composer или бинарник `mago` в PATH |

### Тестирование

| Команда | Инструмент | Автоопределение |
|---------|------------|-----------------|
| `test/phpunit` | [PHPUnit](https://phpunit.de/) | `phpunit/phpunit` в Composer |
| `test/codeception` | [Codeception](https://codeception.com/) | `codeception/codeception` в Composer |
| `test/pest` | [Pest](https://pestphp.com/) | `pestphp/pest` в Composer |
| `test/testo` | [Testo](https://php-testo.github.io/) | `testo/testo` в Composer |

### Прочее

| Команда | Источник | Описание |
|---------|----------|----------|
| `composer/*` | `composer.json` | Все записи `scripts` автоматически обнаруживаются как `composer/{scriptName}` |

## Как это работает

Команды обнаруживаются из трёх источников:

1. **Встроенные команды** — Все команды анализа и тестирования из таблицы выше. Каждая команда имеет проверку `isAvailable()`; недоступные команды скрываются автоматически.
2. **Пользовательские команды** — Дополнительные команды, зарегистрированные через конфигурацию адаптера (`commandMap`).
3. **Скрипты Composer** — Все записи `scripts` из `composer.json` доступны как команды `composer/{scriptName}`.

Нажмите кнопку команды для её выполнения. Вывод отображается в диалоговом окне с индикацией статуса.

## Создание пользовательских команд

Реализуйте `CommandInterface` для создания пользовательской команды:

```php
<?php

declare(strict_types=1);

namespace App\Debug\Command;

use AppDevPanel\Api\Inspector\CommandInterface;
use AppDevPanel\Api\Inspector\CommandResponse;
use AppDevPanel\Api\PathResolverInterface;
use Symfony\Component\Process\Process;

class MyLinterCommand implements CommandInterface
{
    public const COMMAND_NAME = 'analyse/my-linter';

    public function __construct(
        private readonly PathResolverInterface $pathResolver,
    ) {}

    public static function isAvailable(): bool
    {
        return \Composer\InstalledVersions::isInstalled('vendor/my-linter');
    }

    public static function getTitle(): string
    {
        return 'My Linter';
    }

    public static function getDescription(): string
    {
        return 'Run custom linter on the project';
    }

    public function run(): CommandResponse
    {
        $process = new Process(['vendor/bin/my-linter', 'check']);
        $process->setWorkingDirectory($this->pathResolver->getRootPath())
            ->setTimeout(null)
            ->run();

        $output = rtrim($process->getOutput());

        if ($process->getExitCode() > 1) {
            return new CommandResponse(
                status: CommandResponse::STATUS_FAIL,
                result: null,
                errors: array_filter([$output, $process->getErrorOutput()]),
            );
        }

        return new CommandResponse(
            status: $process->isSuccessful()
                ? CommandResponse::STATUS_OK
                : CommandResponse::STATUS_ERROR,
            result: $output,
        );
    }
}
```

### CommandInterface

```php
interface CommandInterface
{
    public static function isAvailable(): bool; // Return false to hide the command
    public static function getTitle(): string;  // Display name in the panel
    public static function getDescription(): string;
    public function run(): CommandResponse;
}
```

### CommandResponse

| Статус | Значение |
|--------|----------|
| `ok` | Команда выполнена успешно |
| `error` | Команда выполнилась, но обнаружила проблемы (например, найдены ошибки линтера) |
| `fail` | Команда не смогла запуститься (например, бинарник не найден, сбой) |

## Регистрация пользовательских команд

Пользовательские команды регистрируются через параметр `commandMap`. Встроенные команды всегда включены — `commandMap` только добавляет дополнительные.

### Yii 3

В параметрах приложения (`config/params.php`):

```php
return [
    'app-dev-panel/yii3' => [
        'api' => [
            'commandMap' => [
                'analyse' => [
                    'analyse/my-linter' => \App\Debug\Command\MyLinterCommand::class,
                ],
            ],
        ],
    ],
];
```

Зарегистрируйте зависимости в DI-конфигурации, если команде нужно внедрение через конструктор:

```php
\App\Debug\Command\MyLinterCommand::class => static fn(
    \AppDevPanel\Api\PathResolverInterface $pathResolver,
) => new \App\Debug\Command\MyLinterCommand($pathResolver),
```

### Symfony

В вашем `config/packages/app_dev_panel.yaml`:

```yaml
services:
    App\Debug\Command\MyLinterCommand:
        arguments:
            - '@AppDevPanel\Api\PathResolverInterface'
```

Переопределите `CommandController` для передачи `commandMap`:

```yaml
services:
    AppDevPanel\Api\Inspector\Controller\CommandController:
        arguments:
            $commandMap:
                analyse:
                    analyse/my-linter: App\Debug\Command\MyLinterCommand
```

### Laravel

В сервис-провайдере:

```php
use AppDevPanel\Api\Inspector\Controller\CommandController;
use AppDevPanel\Api\PathResolverInterface;

$this->app->singleton(MyLinterCommand::class, fn() => new MyLinterCommand(
    $this->app->make(PathResolverInterface::class),
));

$this->app->extend(CommandController::class, function (CommandController $controller) {
    // Built-in commands are already included.
    // Use the commandMap constructor parameter for custom commands.
    return new CommandController(
        $this->app->make(\AppDevPanel\Api\Http\JsonResponseFactoryInterface::class),
        $this->app->make(PathResolverInterface::class),
        $this->app,
        [
            'analyse' => [
                'analyse/my-linter' => MyLinterCommand::class,
            ],
        ],
    );
});
```

### Yii 2

В конфигурации приложения (`config/web.php`):

```php
'modules' => [
    'debug' => [
        'class' => \AppDevPanel\Adapter\Yii2\Module::class,
        'commandMap' => [
            'analyse' => [
                'analyse/my-linter' => \App\Debug\Command\MyLinterCommand::class,
            ],
        ],
    ],
],
```

## API-эндпоинты

| Метод | Путь | Описание |
|-------|------|----------|
| GET | `/inspect/api/command` | Список доступных команд |
| POST | `/inspect/api/command?command=analyse/psalm` | Выполнение команды |

**Формат ответа:**
```json
{
    "status": "ok",
    "result": "...",
    "error": []
}
```

::: tip
Команды PHPUnit и Codeception используют пользовательские JSON-репортеры для структурированного вывода в панели. Остальные команды возвращают простой текстовый вывод.
:::
