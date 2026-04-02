import { defineConfig } from 'vitepress';
import llmstxt from 'vitepress-plugin-llms';
import { tabsMarkdownPlugin } from 'vitepress-plugin-tabs';
import { classLinkPlugin } from './class-link';
import { pkgLinkPlugin } from './pkg-link';

export default defineConfig({
    vite: {
        plugins: [
            llmstxt({
                ignoreFiles: ['ru/**'],
                domain: 'https://app-dev-panel.github.io',
            }),
        ],
    },
    title: 'ADP',
    description: 'Application Development Panel — framework-agnostic debugging and development panel',
    base: '/app-dev-panel/',
    head: [
        ['link', { rel: 'icon', type: 'image/svg+xml', href: '/app-dev-panel/duck.svg' }],
        ['link', { rel: 'preconnect', href: 'https://fonts.googleapis.com' }],
        [
            'link',
            {
                rel: 'preconnect',
                href: 'https://fonts.gstatic.com',
                crossorigin: '',
            },
        ],
        [
            'link',
            {
                rel: 'stylesheet',
                href: 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap',
            },
        ],
        ['meta', { name: 'theme-color', content: '#2563EB' }],
        ['meta', { property: 'og:type', content: 'website' }],
        ['meta', { property: 'og:site_name', content: 'ADP — Application Development Panel' }],
    ],

    locales: {
        root: {
            label: 'English',
            lang: 'en',
            themeConfig: {
                nav: [
                    { text: 'Sponsor', link: '/sponsor' },
                    {
                        text: 'Blog',
                        link: '/blog/',
                        activeMatch: '/blog/',
                    },
                    { text: 'Guide', link: '/guide/getting-started' },
                    { text: 'API', link: '/api/' },
                    { text: 'llms.txt', link: '/guide/llms-txt' },
                    {
                        text: 'Framework',
                        items: [
                            { text: 'Symfony Adapter', link: '/guide/adapters/symfony' },
                            { text: 'Yii 2 Adapter', link: '/guide/adapters/yii2' },
                            { text: 'Yii 3 Adapter', link: '/guide/adapters/yii3' },
                            { text: 'Laravel Adapter', link: '/guide/adapters/laravel' },
                        ],
                    },
                ],
                sidebar: {
                    '/guide/': [
                        {
                            text: 'Introduction',
                            collapsed: false,
                            items: [
                                { text: 'What is ADP?', link: '/guide/what-is-adp' },
                                { text: 'Getting Started', link: '/guide/getting-started' },
                                { text: 'Architecture', link: '/guide/architecture' },
                                { text: 'Roadmap', link: '/guide/roadmap' },
                            ],
                        },
                        {
                            text: 'Core Concepts',
                            collapsed: false,
                            items: [
                                { text: 'Collectors', link: '/guide/collectors' },
                                { text: 'Translator', link: '/guide/translator' },
                                { text: 'Storage', link: '/guide/storage' },
                                { text: 'Proxies', link: '/guide/proxies' },
                                { text: 'Elasticsearch', link: '/guide/elasticsearch' },
                                { text: 'Data Flow', link: '/guide/data-flow' },
                                { text: 'Security & Authorization', link: '/guide/security' },
                                { text: 'Feature Matrix', link: '/guide/feature-matrix' },
                            ],
                        },
                        {
                            text: 'Collector Guides',
                            collapsed: true,
                            items: [
                                { text: 'Log', link: '/guide/collectors/log' },
                                { text: 'Event', link: '/guide/collectors/event' },
                                { text: 'Exception', link: '/guide/collectors/exception' },
                                { text: 'Database', link: '/guide/collectors/database' },
                                { text: 'Cache', link: '/guide/collectors/cache' },
                                { text: 'Redis', link: '/guide/collectors/redis' },
                                { text: 'HTTP Client', link: '/guide/collectors/http-client' },
                                { text: 'Mailer', link: '/guide/collectors/mailer' },
                                { text: 'Queue', link: '/guide/collectors/queue' },
                                { text: 'Validator', link: '/guide/collectors/validator' },
                                { text: 'Router', link: '/guide/collectors/router' },
                                { text: 'Translator', link: '/guide/collectors/translator' },
                                { text: 'Timeline', link: '/guide/collectors/timeline' },
                                { text: 'VarDumper', link: '/guide/collectors/var-dumper' },
                                { text: 'Request', link: '/guide/collectors/request' },
                                { text: 'Environment', link: '/guide/collectors/environment' },
                                { text: 'Elasticsearch', link: '/guide/collectors/elasticsearch' },
                                { text: 'OpenTelemetry', link: '/guide/collectors/opentelemetry' },
                                { text: 'Authorization', link: '/guide/collectors/authorization' },
                                { text: 'Deprecation', link: '/guide/collectors/deprecation' },
                                { text: 'Service', link: '/guide/collectors/service' },
                                { text: 'Middleware', link: '/guide/collectors/middleware' },
                                { text: 'Template', link: '/guide/collectors/template' },
                                { text: 'AssetBundle', link: '/guide/collectors/asset-bundle' },
                                { text: 'WebAppInfo', link: '/guide/collectors/web-app-info' },
                                { text: 'Command', link: '/guide/collectors/command' },
                                { text: 'ConsoleAppInfo', link: '/guide/collectors/console-app-info' },
                                { text: 'Filesystem Stream', link: '/guide/collectors/filesystem-stream' },
                                { text: 'HTTP Stream', link: '/guide/collectors/http-stream' },
                            ],
                        },
                        {
                            text: 'Inspector Guides',
                            collapsed: true,
                            items: [
                                { text: 'Routes', link: '/guide/inspector/routes' },
                                { text: 'Event Listeners', link: '/guide/inspector/events' },
                                { text: 'Configuration', link: '/guide/inspector/config' },
                                { text: 'Database', link: '/guide/inspector/database' },
                                { text: 'File Explorer', link: '/guide/inspector/files' },
                                { text: 'Commands', link: '/guide/inspector/commands' },
                                { text: 'Composer', link: '/guide/inspector/composer' },
                                { text: 'Git', link: '/guide/inspector/git' },
                                { text: 'Authorization', link: '/guide/inspector/authorization' },
                                { text: 'Cache', link: '/guide/inspector/cache' },
                                { text: 'Redis', link: '/guide/inspector/redis' },
                                { text: 'Elasticsearch', link: '/guide/inspector/elasticsearch' },
                                { text: 'Translations', link: '/guide/inspector/translations' },
                                { text: 'PHP Info', link: '/guide/inspector/phpinfo' },
                                { text: 'OPcache', link: '/guide/inspector/opcache' },
                                { text: 'Code Coverage', link: '/guide/inspector/coverage' },
                            ],
                        },
                        {
                            text: 'Adapters',
                            collapsed: false,
                            items: [
                                { text: 'Symfony', link: '/guide/adapters/symfony' },
                                { text: 'Yii 2', link: '/guide/adapters/yii2' },
                                { text: 'Yii 3', link: '/guide/adapters/yii3' },
                                { text: 'Laravel', link: '/guide/adapters/laravel' },
                            ],
                        },
                        {
                            text: 'Frontend',
                            collapsed: false,
                            items: [
                                { text: 'Debug Panel', link: '/guide/debug-panel' },
                                { text: 'Toolbar', link: '/guide/toolbar' },
                                { text: 'Frontend Packages', link: '/guide/frontend-packages' },
                            ],
                        },
                        {
                            text: 'Advanced',
                            collapsed: true,
                            items: [
                                { text: 'MCP Server', link: '/guide/mcp-server' },
                                { text: 'CLI', link: '/guide/cli' },
                                { text: 'Playgrounds', link: '/guide/playgrounds' },
                                { text: 'CI & Tooling', link: '/guide/ci-and-tooling' },
                                { text: 'Screenshots', link: '/guide/screenshots' },
                                { text: 'Editor Integration', link: '/guide/editor-integration' },
                                { text: 'llms.txt', link: '/guide/llms-txt' },
                                { text: 'Contributing', link: '/guide/contributing' },
                            ],
                        },
                    ],
                    '/api/': [
                        {
                            text: 'API Reference',
                            items: [
                                { text: 'Overview', link: '/api/' },
                                { text: 'REST Endpoints', link: '/api/rest' },
                                { text: 'SSE', link: '/api/sse' },
                                { text: 'Inspector', link: '/api/inspector' },
                            ],
                        },
                    ],
                },
                editLink: {
                    pattern: 'https://github.com/app-dev-panel/app-dev-panel/edit/master/website/:path',
                    text: 'Edit this page on GitHub',
                },
                footer: {
                    message: 'Released under the MIT License.',
                    copyright: 'Copyright © 2024-present ADP Contributors',
                },
            },
        },
        ru: {
            label: 'Русский',
            lang: 'ru',
            link: '/ru/',
            themeConfig: {
                nav: [
                    { text: 'Спонсоры', link: '/ru/sponsor' },
                    {
                        text: 'Блог',
                        link: '/ru/blog/',
                        activeMatch: '/ru/blog/',
                    },
                    { text: 'Руководство', link: '/ru/guide/getting-started' },
                    { text: 'API', link: '/ru/api/' },
                    { text: 'llms.txt', link: '/ru/guide/llms-txt' },
                    {
                        text: 'Фреймворки',
                        items: [
                            { text: 'Адаптер Symfony', link: '/ru/guide/adapters/symfony' },
                            { text: 'Адаптер Yii 2', link: '/ru/guide/adapters/yii2' },
                            { text: 'Адаптер Yii 3', link: '/ru/guide/adapters/yii3' },
                            { text: 'Адаптер Laravel', link: '/ru/guide/adapters/laravel' },
                        ],
                    },
                ],
                sidebar: {
                    '/ru/guide/': [
                        {
                            text: 'Введение',
                            collapsed: false,
                            items: [
                                { text: 'Что такое ADP?', link: '/ru/guide/what-is-adp' },
                                { text: 'Начало работы', link: '/ru/guide/getting-started' },
                                { text: 'Архитектура', link: '/ru/guide/architecture' },
                                { text: 'Дорожная карта', link: '/ru/guide/roadmap' },
                            ],
                        },
                        {
                            text: 'Основные концепции',
                            collapsed: false,
                            items: [
                                { text: 'Коллекторы', link: '/ru/guide/collectors' },
                                { text: 'Переводчик', link: '/ru/guide/translator' },
                                { text: 'Хранилище', link: '/ru/guide/storage' },
                                { text: 'Прокси', link: '/ru/guide/proxies' },
                                { text: 'Elasticsearch', link: '/ru/guide/elasticsearch' },
                                { text: 'Поток данных', link: '/ru/guide/data-flow' },
                                { text: 'Безопасность и авторизация', link: '/ru/guide/security' },
                                { text: 'Матрица возможностей', link: '/ru/guide/feature-matrix' },
                            ],
                        },
                        {
                            text: 'Руководства по коллекторам',
                            collapsed: true,
                            items: [
                                { text: 'Log', link: '/ru/guide/collectors/log' },
                                { text: 'Event', link: '/ru/guide/collectors/event' },
                                { text: 'Exception', link: '/ru/guide/collectors/exception' },
                                { text: 'Database', link: '/ru/guide/collectors/database' },
                                { text: 'Cache', link: '/ru/guide/collectors/cache' },
                                { text: 'Redis', link: '/ru/guide/collectors/redis' },
                                { text: 'HTTP Client', link: '/ru/guide/collectors/http-client' },
                                { text: 'Mailer', link: '/ru/guide/collectors/mailer' },
                                { text: 'Queue', link: '/ru/guide/collectors/queue' },
                                { text: 'Validator', link: '/ru/guide/collectors/validator' },
                                { text: 'Router', link: '/ru/guide/collectors/router' },
                                { text: 'Translator', link: '/ru/guide/collectors/translator' },
                                { text: 'Timeline', link: '/ru/guide/collectors/timeline' },
                                { text: 'VarDumper', link: '/ru/guide/collectors/var-dumper' },
                                { text: 'Request', link: '/ru/guide/collectors/request' },
                                { text: 'Environment', link: '/ru/guide/collectors/environment' },
                                { text: 'Elasticsearch', link: '/ru/guide/collectors/elasticsearch' },
                                { text: 'OpenTelemetry', link: '/ru/guide/collectors/opentelemetry' },
                                { text: 'Authorization', link: '/ru/guide/collectors/authorization' },
                                { text: 'Deprecation', link: '/ru/guide/collectors/deprecation' },
                                { text: 'Service', link: '/ru/guide/collectors/service' },
                                { text: 'Middleware', link: '/ru/guide/collectors/middleware' },
                                { text: 'Template', link: '/ru/guide/collectors/template' },
                                { text: 'AssetBundle', link: '/ru/guide/collectors/asset-bundle' },
                                { text: 'WebAppInfo', link: '/ru/guide/collectors/web-app-info' },
                                { text: 'Command', link: '/ru/guide/collectors/command' },
                                { text: 'ConsoleAppInfo', link: '/ru/guide/collectors/console-app-info' },
                                { text: 'Filesystem Stream', link: '/ru/guide/collectors/filesystem-stream' },
                                { text: 'HTTP Stream', link: '/ru/guide/collectors/http-stream' },
                            ],
                        },
                        {
                            text: 'Руководства по инспектору',
                            collapsed: true,
                            items: [
                                { text: 'Маршруты', link: '/ru/guide/inspector/routes' },
                                { text: 'Слушатели событий', link: '/ru/guide/inspector/events' },
                                { text: 'Конфигурация', link: '/ru/guide/inspector/config' },
                                { text: 'База данных', link: '/ru/guide/inspector/database' },
                                { text: 'Файловый менеджер', link: '/ru/guide/inspector/files' },
                                { text: 'Команды', link: '/ru/guide/inspector/commands' },
                                { text: 'Composer', link: '/ru/guide/inspector/composer' },
                                { text: 'Git', link: '/ru/guide/inspector/git' },
                                { text: 'Авторизация', link: '/ru/guide/inspector/authorization' },
                                { text: 'Кеш', link: '/ru/guide/inspector/cache' },
                                { text: 'Redis', link: '/ru/guide/inspector/redis' },
                                { text: 'Elasticsearch', link: '/ru/guide/inspector/elasticsearch' },
                                { text: 'Переводы', link: '/ru/guide/inspector/translations' },
                                { text: 'PHP Info', link: '/ru/guide/inspector/phpinfo' },
                                { text: 'OPcache', link: '/ru/guide/inspector/opcache' },
                                { text: 'Покрытие кода', link: '/ru/guide/inspector/coverage' },
                            ],
                        },
                        {
                            text: 'Адаптеры',
                            collapsed: false,
                            items: [
                                { text: 'Symfony', link: '/ru/guide/adapters/symfony' },
                                { text: 'Yii 2', link: '/ru/guide/adapters/yii2' },
                                { text: 'Yii 3', link: '/ru/guide/adapters/yii3' },
                                { text: 'Laravel', link: '/ru/guide/adapters/laravel' },
                            ],
                        },
                        {
                            text: 'Фронтенд',
                            collapsed: false,
                            items: [
                                { text: 'Панель отладки', link: '/ru/guide/debug-panel' },
                                { text: 'Тулбар', link: '/ru/guide/toolbar' },
                                { text: 'Фронтенд-пакеты', link: '/ru/guide/frontend-packages' },
                            ],
                        },
                        {
                            text: 'Продвинутое',
                            collapsed: true,
                            items: [
                                { text: 'MCP Сервер', link: '/ru/guide/mcp-server' },
                                { text: 'CLI', link: '/ru/guide/cli' },
                                { text: 'Playground-приложения', link: '/ru/guide/playgrounds' },
                                { text: 'CI и инструменты', link: '/ru/guide/ci-and-tooling' },
                                { text: 'Скриншоты', link: '/ru/guide/screenshots' },
                                { text: 'Интеграция с редакторами', link: '/ru/guide/editor-integration' },
                                { text: 'llms.txt', link: '/ru/guide/llms-txt' },
                                { text: 'Участие в разработке', link: '/ru/guide/contributing' },
                            ],
                        },
                    ],
                    '/ru/api/': [
                        {
                            text: 'Справочник API',
                            items: [
                                { text: 'Обзор', link: '/ru/api/' },
                                { text: 'REST Эндпоинты', link: '/ru/api/rest' },
                                { text: 'SSE', link: '/ru/api/sse' },
                                { text: 'Инспектор', link: '/ru/api/inspector' },
                            ],
                        },
                    ],
                },
                editLink: {
                    pattern: 'https://github.com/app-dev-panel/app-dev-panel/edit/master/website/:path',
                    text: 'Редактировать на GitHub',
                },
                footer: {
                    message: 'Выпущено под лицензией MIT.',
                    copyright: 'Copyright © 2024-present Участники ADP',
                },
                docFooter: {
                    prev: 'Предыдущая',
                    next: 'Следующая',
                },
                outline: {
                    label: 'Содержание',
                },
                lastUpdated: {
                    text: 'Обновлено',
                },
                returnToTopLabel: 'Наверх',
                sidebarMenuLabel: 'Меню',
                darkModeSwitchLabel: 'Тема',
                langMenuLabel: 'Язык',
            },
        },
    },

    themeConfig: {
        logo: '/duck.svg',
        siteTitle: 'ADP',
        socialLinks: [],
        search: {
            provider: 'local',
            options: {
                locales: {
                    ru: {
                        translations: {
                            button: {
                                buttonText: 'Поиск',
                                buttonAriaLabel: 'Поиск',
                            },
                            modal: {
                                displayDetails: 'Показать детали',
                                resetButtonTitle: 'Сбросить',
                                backButtonTitle: 'Назад',
                                noResultsText: 'Ничего не найдено',
                                footer: {
                                    selectText: 'выбрать',
                                    navigateText: 'навигация',
                                    closeText: 'закрыть',
                                },
                            },
                        },
                    },
                },
            },
        },
    },

    markdown: {
        lineNumbers: true,
        image: {
            lazyLoading: true,
        },
        config(md) {
            md.use(tabsMarkdownPlugin);
            md.use(classLinkPlugin);
            md.use(pkgLinkPlugin);
        },
    },

    sitemap: {
        hostname: 'https://app-dev-panel.github.io/app-dev-panel/',
    },

    lastUpdated: true,
});
