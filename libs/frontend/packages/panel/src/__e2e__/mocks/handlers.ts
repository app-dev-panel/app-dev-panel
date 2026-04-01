import {http, HttpResponse} from 'msw';

const BASE = 'http://127.0.0.1:8080';

const debugEntries = [
    {
        id: 'entry-001',
        collectors: [
            {id: 'App\\Collector\\WebCollector', name: 'Web'},
            {id: 'App\\Collector\\LogCollector', name: 'Log'},
            {id: 'App\\Collector\\DatabaseCollector', name: 'Database'},
            {id: 'App\\Collector\\EventCollector', name: 'Event'},
        ],
        web: {
            php: {version: '8.4.18'},
            request: {startTime: 1700000000, processingTime: 0.042},
            memory: {peakUsage: 4194304},
        },
        request: {
            url: 'http://localhost/api/test',
            path: '/api/test',
            query: '',
            method: 'GET',
            isAjax: false,
            userIp: '127.0.0.1',
        },
        response: {statusCode: 200},
        logger: {total: 5},
        event: {total: 12},
        db: {queries: {error: 0, total: 3}, transactions: {error: 0, total: 1}},
    },
    {
        id: 'entry-002',
        collectors: [{id: 'App\\Collector\\WebCollector', name: 'Web'}],
        web: {
            php: {version: '8.4.18'},
            request: {startTime: 1700000100, processingTime: 0.015},
            memory: {peakUsage: 2097152},
        },
        request: {
            url: 'http://localhost/api/users',
            path: '/api/users',
            query: 'page=1',
            method: 'POST',
            isAjax: true,
            userIp: '10.0.0.1',
        },
        response: {statusCode: 201},
    },
];

const collectorInfoData = {
    'App\\Collector\\LogCollector': [
        {level: 'info', message: 'Application started', context: {}, category: 'app', time: 1700000000.123},
        {level: 'debug', message: 'Route matched: /api/test', context: {}, category: 'router', time: 1700000000.124},
        {level: 'warning', message: 'Deprecated function used', context: {}, category: 'app', time: 1700000000.125},
    ],
    'App\\Collector\\WebCollector': {
        request: {method: 'GET', url: '/api/test', headers: {'Content-Type': 'application/json'}},
        response: {statusCode: 200, headers: {'Content-Type': 'application/json'}},
    },
};

export const handlers = [
    // Debug API - list entries
    http.get(`${BASE}/debug/api/`, () => {
        return HttpResponse.json({data: debugEntries});
    }),

    // Debug API - view entry by ID with collector
    http.get(`${BASE}/debug/api/view/:id`, ({request}) => {
        const url = new URL(request.url);
        const collector = url.searchParams.get('collector');
        const data = (collector && collectorInfoData[collector as keyof typeof collectorInfoData]) || [];
        return HttpResponse.json({data});
    }),

    // Debug API - object
    http.get(`${BASE}/debug/api/object/:entryId/:objectId`, () => {
        return HttpResponse.json({data: {class: 'App\\Model\\User', value: {id: 1, name: 'Test'}}});
    }),

    // Services API
    http.get(`${BASE}/debug/api/services/`, () => {
        return HttpResponse.json({data: []});
    }),

    // Inspector - config
    http.get(`${BASE}/inspect/api/params`, () => {
        return HttpResponse.json({
            data: {groups: ['app', 'web', 'params'], params: {'app.name': 'Test App', 'app.debug': true}},
        });
    }),

    // Inspector - routes
    http.get(`${BASE}/inspect/api/routes`, () => {
        return HttpResponse.json({
            data: [
                {name: 'home', pattern: '/', methods: ['GET'], host: '', action: 'HomeController::index'},
                {
                    name: 'api.users',
                    pattern: '/api/users',
                    methods: ['GET', 'POST'],
                    host: '',
                    action: 'UserController::list',
                },
            ],
        });
    }),

    // Inspector - git summary
    http.get(`${BASE}/debug/api/inspector/git/summary`, () => {
        return HttpResponse.json({
            data: {branch: 'main', commit: 'abc1234', remotes: [{name: 'origin', url: 'https://github.com/test/repo'}]},
        });
    }),

    // Inspector - git log
    http.get(`${BASE}/debug/api/inspector/git/log`, () => {
        return HttpResponse.json({
            data: [
                {hash: 'abc1234', message: 'Initial commit', author: 'Dev', date: '2024-01-01'},
                {hash: 'def5678', message: 'Add feature', author: 'Dev', date: '2024-01-02'},
            ],
        });
    }),

    // Inspector - commands
    http.get(`${BASE}/inspect/api/commands`, () => {
        return HttpResponse.json({
            data: [
                {name: 'migrate', description: 'Run migrations'},
                {name: 'cache:clear', description: 'Clear cache'},
            ],
        });
    }),

    // Inspector - database tables
    http.get(`${BASE}/inspect/api/database/tables`, () => {
        return HttpResponse.json({
            data: [
                {name: 'users', rowCount: 42},
                {name: 'posts', rowCount: 100},
            ],
        });
    }),

    // Inspector - files
    http.get(`${BASE}/inspect/api/files`, () => {
        return HttpResponse.json({
            data: {
                path: '/',
                children: [
                    {name: 'src', type: 'directory'},
                    {name: 'composer.json', type: 'file'},
                ],
            },
        });
    }),

    // Inspector - events
    http.get(`${BASE}/inspect/api/events`, () => {
        return HttpResponse.json({
            data: [
                {name: 'app.request', listeners: 3},
                {name: 'app.response', listeners: 2},
            ],
        });
    }),

    // Inspector - translations
    http.get(`${BASE}/inspect/api/translations`, () => {
        return HttpResponse.json({data: {locales: ['en', 'ru'], categories: ['app', 'error']}});
    }),

    // Inspector - PHP info
    http.get(`${BASE}/inspect/api/phpinfo`, () => {
        return HttpResponse.json({data: {version: '8.4.18', extensions: ['pdo', 'mbstring', 'json']}});
    }),

    // Inspector - composer
    http.get(`${BASE}/inspect/api/composer`, () => {
        return HttpResponse.json({
            data: {
                installed: [
                    {name: 'yiisoft/core', version: '1.0.0'},
                    {name: 'yiisoft/router', version: '2.0.0'},
                ],
            },
        });
    }),

    // Inspector - opcache
    http.get(`${BASE}/inspect/api/opcache`, () => {
        return HttpResponse.json({data: {enabled: true, hitRate: 95.5, memoryUsage: {used: 50, free: 50}}});
    }),

    // Inspector - cache
    http.get(`${BASE}/inspect/api/cache`, () => {
        return HttpResponse.json({data: {items: [], pools: []}});
    }),

    // Inspector - container classes
    http.get(`${BASE}/inspect/api/classes`, () => {
        return HttpResponse.json({
            data: ['App\\Controller\\HomeController', 'App\\Service\\UserService', 'Psr\\Log\\LoggerInterface'],
        });
    }),

    // Inspector - object
    http.get(`${BASE}/inspect/api/object`, () => {
        return HttpResponse.json({data: {object: {class: 'Test', active: true}, path: '/src'}});
    }),

    // Inspector - configuration (DI definitions)
    http.get(`${BASE}/inspect/api/config`, () => {
        return HttpResponse.json({
            data: {
                assetManager: 'yii\\web\\AssetManager',
                db: 'yii\\db\\Connection',
                errorHandler: 'yii\\web\\ErrorHandler',
            },
        });
    }),

    // GenCode API
    http.get(`${BASE}/gen-code/api/generator`, () => {
        return HttpResponse.json({data: []});
    }),

    // SSE event stream - return empty
    http.get(`${BASE}/debug/api/event-stream`, () => {
        return new HttpResponse(null, {status: 204});
    }),
];
