import { defineConfig } from 'vitepress';

export default defineConfig({
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
        en: {
            label: 'English',
            lang: 'en',
            link: '/en/',
            themeConfig: {
                nav: [
                    { text: 'Guide', link: '/en/guide/getting-started' },
                    { text: 'API', link: '/en/api/' },
                    {
                        text: 'Blog',
                        link: '/en/blog/',
                        activeMatch: '/en/blog/',
                    },
                    {
                        text: 'Ecosystem',
                        items: [
                            { text: 'Yii 3 Adapter', link: '/en/guide/adapters/yiisoft' },
                            { text: 'Symfony Adapter', link: '/en/guide/adapters/symfony' },
                            { text: 'Laravel Adapter', link: '/en/guide/adapters/laravel' },
                            { text: 'Yii 2 Adapter', link: '/en/guide/adapters/yii2' },
                            { text: 'Cycle ORM Adapter', link: '/en/guide/adapters/cycle' },
                        ],
                    },
                ],
                sidebar: {
                    '/en/guide/': [
                        {
                            text: 'Introduction',
                            collapsed: false,
                            items: [
                                { text: 'What is ADP?', link: '/en/guide/what-is-adp' },
                                { text: 'Getting Started', link: '/en/guide/getting-started' },
                                { text: 'Architecture', link: '/en/guide/architecture' },
                            ],
                        },
                        {
                            text: 'Core Concepts',
                            collapsed: false,
                            items: [
                                { text: 'Collectors', link: '/en/guide/collectors' },
                                { text: 'Storage', link: '/en/guide/storage' },
                                { text: 'Proxies', link: '/en/guide/proxies' },
                                { text: 'Data Flow', link: '/en/guide/data-flow' },
                            ],
                        },
                        {
                            text: 'Adapters',
                            collapsed: false,
                            items: [
                                { text: 'Yii 3 (Yiisoft)', link: '/en/guide/adapters/yiisoft' },
                                { text: 'Symfony', link: '/en/guide/adapters/symfony' },
                                { text: 'Laravel', link: '/en/guide/adapters/laravel' },
                                { text: 'Yii 2', link: '/en/guide/adapters/yii2' },
                                { text: 'Cycle ORM', link: '/en/guide/adapters/cycle' },
                            ],
                        },
                        {
                            text: 'Advanced',
                            collapsed: true,
                            items: [
                                { text: 'MCP Server', link: '/en/guide/mcp-server' },
                                { text: 'CLI', link: '/en/guide/cli' },
                                { text: 'Contributing', link: '/en/guide/contributing' },
                            ],
                        },
                    ],
                    '/en/api/': [
                        {
                            text: 'API Reference',
                            items: [
                                { text: 'Overview', link: '/en/api/' },
                                { text: 'REST Endpoints', link: '/en/api/rest' },
                                { text: 'SSE', link: '/en/api/sse' },
                                { text: 'Inspector', link: '/en/api/inspector' },
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
                                { text: 'Поток данных', link: '/ru/guide/data-flow' },
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
