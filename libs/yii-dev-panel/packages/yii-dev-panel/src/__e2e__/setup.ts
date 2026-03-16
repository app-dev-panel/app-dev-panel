import {setupWorker} from 'msw/browser';
import {afterAll, afterEach, beforeAll} from 'vitest';
import {handlers} from './mocks/handlers';

const worker = setupWorker(...handlers);

beforeAll(async () => {
    await worker.start({onUnhandledRequest: 'bypass'});
});

afterEach(() => {
    worker.resetHandlers();
});

afterAll(() => {
    worker.stop();
});

export {worker};
