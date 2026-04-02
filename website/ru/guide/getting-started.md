---
title: Начало работы
description: "Установка ADP в PHP-приложение. Быстрая настройка для Symfony, Laravel, Yii 3 и Yii 2 через Composer."
---

# Начало работы

ADP (Application Development Panel) — это фреймворк-независимая панель отладки для PHP-приложений. Она собирает данные во время выполнения и предоставляет веб-интерфейс для их анализа.

<div class="badges">
  <a href="https://packagist.org/packages/app-dev-panel/kernel"><img src="https://img.shields.io/packagist/dependency-v/app-dev-panel/kernel/php?style=flat-square" alt="php"></a>
  <a href="https://packagist.org/packages/app-dev-panel/kernel"><img src="https://img.shields.io/packagist/v/app-dev-panel/kernel?style=flat-square" alt="packagist"></a>
  <a href="https://github.com/app-dev-panel/app-dev-panel/blob/master/LICENSE"><img src="https://img.shields.io/github/license/app-dev-panel/app-dev-panel?style=flat-square" alt="license"></a>
  <a href="https://packagist.org/packages/app-dev-panel/kernel"><img src="https://img.shields.io/packagist/dt/app-dev-panel/kernel?style=flat-square" alt="downloads"></a>
  <a href="https://github.com/app-dev-panel/app-dev-panel"><img src="https://img.shields.io/github/stars/app-dev-panel/app-dev-panel?style=flat-square" alt="github stars"></a>
</div>

<style>
.badges {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}
</style>

## Требования

- PHP 8.4 или выше
- Composer

## Установка

### 1. Установите адаптер для вашего фреймворка

:::tabs key:framework
== Symfony
```bash
composer require app-dev-panel/adapter-symfony
```
== Yii 2
```bash
composer require app-dev-panel/adapter-yii2
```
== Yii 3
```bash
composer require app-dev-panel/adapter-yii3
```
== Laravel
```bash
composer require app-dev-panel/adapter-laravel
```
:::

Каждый адаптер автоматически подтягивает <pkg>app-dev-panel/kernel</pkg> и <pkg>app-dev-panel/api</pkg> как зависимости.

### 2. Сконфигурируйте приложение

:::tabs key:framework
== Symfony
```php
// config/bundles.php
return [
    // ...
    AppDevPanel\Adapter\Symfony\AppDevPanelBundle::class => ['dev' => true, 'test' => true],
];
```
== Yii 2
```php
// config/web.php
return [
    'bootstrap' => ['adp'],
    'modules' => [
        'adp' => [
            'class' => \AppDevPanel\Adapter\Yii2\Module::class,
        ],
    ],
];
```
== Yii 3
```php
// Конфигурация не нужна — авторегистрация через yiisoft/config plugin
```
== Laravel
```php
// Авторегистрация через package discovery
// Опционально опубликуйте конфиг:
// php artisan vendor:publish --tag=app-dev-panel-config
```
:::

### 3. Начинайте отладку

Запустите приложение и откройте `http://your-app/debug` в браузере. [Панель отладки](/ru/guide/debug-panel) ADP покажет данные отладки, собранные из вашего приложения в реальном времени.

::: tip Встроенный сервер PHP
При использовании встроенного сервера PHP всегда устанавливайте `PHP_CLI_SERVER_WORKERS=3` или выше. ADP выполняет параллельные запросы (SSE + получение данных); однопоточный режим вызывает таймауты.

```bash
PHP_CLI_SERVER_WORKERS=3 php -S 127.0.0.1:8080 -t public
```
:::

## Попробуйте демо

Попробуйте UI панели прямо сейчас с [Live Demo](https://app-dev-panel.github.io/app-dev-panel/demo/) — установка не требуется. Введите URL бэкенда вашего приложения для подключения.

ADP также поставляется с [playground-приложениями](/ru/guide/playgrounds) для каждого поддерживаемого фреймворка:

```bash
git clone https://github.com/app-dev-panel/app-dev-panel.git
cd app-dev-panel
make install              # Установить все зависимости
```

Запустите playground-сервер:

:::tabs key:framework
== Symfony
```bash
cd playground/symfony-app && PHP_CLI_SERVER_WORKERS=3 php -S 127.0.0.1:8102 -t public
```
== Yii 2
```bash
cd playground/yii2-basic-app && PHP_CLI_SERVER_WORKERS=3 php -S 127.0.0.1:8103 -t public
```
== Yii 3
```bash
cd playground/yii3-app && ./yii serve --port=8101
```
== Laravel
```bash
cd playground/laravel-app && PHP_CLI_SERVER_WORKERS=3 php -S 127.0.0.1:8104 -t public
```
:::

## Обзор архитектуры

ADP использует многослойную архитектуру:

```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│   Frontend   │────▶│     API      │────▶│    Kernel     │
│  (React SPA) │ HTTP│  (REST+SSE)  │     │ (Collectors)  │
└──────────────┘     └──────────────┘     └───────┬───────┘
                                                  │
                                          ┌───────┴───────┐
                                          │    Adapter     │
                                          └───────┬───────┘
                                                  │
                                          ┌───────┴───────┐
                                          │  Target App   │
                                          └───────────────┘
```

1. **Kernel** — Ядро, управляющее жизненным циклом отладчика, коллекторами и хранилищем
2. **API** — HTTP-слой, предоставляющий данные отладки через REST + SSE
3. **Adapter** — Мост к фреймворку, подключающий коллекторы к вашему приложению
4. **Frontend** — React SPA, использующий API

## Что дальше?

- [Что такое ADP?](/ru/guide/what-is-adp) — Философия проекта
- [Панель отладки](/ru/guide/debug-panel) — Настройка встроенной панели отладки
- [Архитектура](/ru/guide/architecture) — Глубокое погружение в дизайн системы
- [Коллекторы](/ru/guide/collectors) — Как собираются данные
- [Поток данных](/ru/guide/data-flow) — Путь данных от приложения до панели
- [Матрица возможностей](/ru/guide/feature-matrix) — Что поддерживается в каждом фреймворке
- [Playground-приложения](/ru/guide/playgrounds) — Попробуйте демо-приложения
