import { defineConfig } from 'vitepress';
import llmstxt from 'vitepress-plugin-llms';

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
                    { text: 'Guide', link: '/guide/getting-started' },
                    { text: 'API', link: '/api/' },
                    { text: 'llms.txt', link: '/guide/llms-txt' },
                    {
                        text: 'Blog',
                        link: '/blog/',
                        activeMatch: '/blog/',
                    },
                    {
                        text: 'Ecosystem',
                        items: [
                            { text: 'Yii 3 Adapter', link: '/guide/adapters/yiisoft' },
                            { text: 'Symfony Adapter', link: '/guide/adapters/symfony' },
                            { text: 'Laravel Adapter', link: '/guide/adapters/laravel' },
                            { text: 'Yii 2 Adapter', link: '/guide/adapters/yii2' },
                            { text: 'Cycle ORM Adapter', link: '/guide/adapters/cycle' },
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
                            ],
                        },
                        {
                            text: 'Core Concepts',
                            collapsed: false,
                            items: [
                                { text: 'Collectors', link: '/guide/collectors' },
                                { text: 'Storage', link: '/guide/storage' },
                                { text: 'Proxies', link: '/guide/proxies' },
                                { text: 'Elasticsearch', link: '/guide/elasticsearch' },
                                { text: 'Data Flow', link: '/guide/data-flow' },
                                { text: 'Security & Authorization', link: '/guide/security' },
                            ],
                        },
                        {
                            text: 'Collector Guides',
                            collapsed: false,
                            items: [
                                { text: 'Redis', link: '/guide/redis' },
                            ],
                        },
                        {
                            text: 'Adapters',
                            collapsed: false,
                            items: [
                                { text: 'Yii 3 (Yiisoft)', link: '/guide/adapters/yiisoft' },
                                { text: 'Symfony', link: '/guide/adapters/symfony' },
                                { text: 'Laravel', link: '/guide/adapters/laravel' },
                                { text: 'Yii 2', link: '/guide/adapters/yii2' },
                                { text: 'Cycle ORM', link: '/guide/adapters/cycle' },
                            ],
                        },
                        {
                            text: 'Advanced',
                            collapsed: true,
                            items: [
                                { text: 'MCP Server', link: '/guide/mcp-server' },
                                { text: 'CLI', link: '/guide/cli' },
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
                    { text: 'Руководство', link: '/ru/guide/getting-started' },
                    { text: 'API', link: '/ru/api/' },
                    { text: 'llms.txt', link: '/ru/guide/llms-txt' },
                    {
                        text: 'Блог',
                        link: '/ru/blog/',
                        activeMatch: '/ru/blog/',
                    },
                    {
                        text: 'Экосистема',
                        items: [
                            { text: 'Адаптер Yii 3', link: '/ru/guide/adapters/yiisoft' },
                            { text: 'Адаптер Symfony', link: '/ru/guide/adapters/symfony' },
                            { text: 'Адаптер Laravel', link: '/ru/guide/adapters/laravel' },
                            { text: 'Адаптер Yii 2', link: '/ru/guide/adapters/yii2' },
                            { text: 'Адаптер Cycle ORM', link: '/ru/guide/adapters/cycle' },
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
                            ],
                        },
                        {
                            text: 'Основные концепции',
                            collapsed: false,
                            items: [
                                { text: 'Коллекторы', link: '/ru/guide/collectors' },
                                { text: 'Хранилище', link: '/ru/guide/storage' },
                                { text: 'Прокси', link: '/ru/guide/proxies' },
                                { text: 'Elasticsearch', link: '/ru/guide/elasticsearch' },
                                { text: 'Поток данных', link: '/ru/guide/data-flow' },
                                { text: 'Безопасность и авторизация', link: '/ru/guide/security' },
                            ],
                        },
                        {
                            text: 'Руководства по коллекторам',
                            collapsed: false,
                            items: [
                                { text: 'Redis', link: '/ru/guide/redis' },
                            ],
                        },
                        {
                            text: 'Адаптеры',
                            collapsed: false,
                            items: [
                                { text: 'Yii 3 (Yiisoft)', link: '/ru/guide/adapters/yiisoft' },
                                { text: 'Symfony', link: '/ru/guide/adapters/symfony' },
                                { text: 'Laravel', link: '/ru/guide/adapters/laravel' },
                                { text: 'Yii 2', link: '/ru/guide/adapters/yii2' },
                                { text: 'Cycle ORM', link: '/ru/guide/adapters/cycle' },
                            ],
                        },
                        {
                            text: 'Продвинутое',
                            collapsed: true,
                            items: [
                                { text: 'MCP Сервер', link: '/ru/guide/mcp-server' },
                                { text: 'CLI', link: '/ru/guide/cli' },
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
        socialLinks: [
            { icon: 'github', link: 'https://github.com/app-dev-panel/app-dev-panel' },
        ],
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
    },

    sitemap: {
        hostname: 'https://app-dev-panel.github.io/app-dev-panel/',
    },

    lastUpdated: true,
});
