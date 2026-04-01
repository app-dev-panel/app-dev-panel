import {http, HttpResponse} from 'msw';

const BASE = 'http://127.0.0.1:8080';

const debugEntries = [
    {
        id: 'toolbar-entry-001',
        collectors: [{id: 'App\\Collector\\WebCollector', name: 'Web'}],
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
        router: {name: 'api.test', action: 'TestController::index', middlewares: ['auth', 'cors']},
        validator: {total: 0},
    },
    {
        id: 'toolbar-entry-002',
        collectors: [{id: 'App\\Collector\\WebCollector', name: 'Web'}],
        web: {
            php: {version: '8.4.18'},
            request: {startTime: 1700000100, processingTime: 0.125},
            memory: {peakUsage: 8388608},
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
        logger: {total: 3},
        event: {total: 8},
    },
];

export const handlers = [
    http.get(`${BASE}/debug/api/`, () => {
        return HttpResponse.json({data: debugEntries});
    }),

    http.get(`${BASE}/debug/api/view/:id`, ({request}) => {
        const url = new URL(request.url);
        const collector = url.searchParams.get('collector');
        return HttpResponse.json({data: collector ? [] : {}});
    }),

    http.get(`${BASE}/debug/api/summary/:id`, () => {
        return HttpResponse.json({data: debugEntries[0]});
    }),

    http.get(`${BASE}/debug/api/services/`, () => {
        return HttpResponse.json({data: []});
    }),

    http.get(`${BASE}/debug/api/event-stream`, () => {
        return new HttpResponse(null, {status: 204});
    }),
];

export const emptyHandlers = [
    http.get(`${BASE}/debug/api/`, () => {
        return HttpResponse.json({data: []});
    }),

    http.get(`${BASE}/debug/api/services/`, () => {
        return HttpResponse.json({data: []});
    }),

    http.get(`${BASE}/debug/api/event-stream`, () => {
        return new HttpResponse(null, {status: 204});
    }),
];
