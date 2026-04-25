import {page} from '@vitest/browser/context';
import {setupWorker} from 'msw/browser';
import {afterAll, afterEach, beforeAll} from 'vitest';
import {handlers} from './mocks/handlers';

const worker = setupWorker(...handlers);

beforeAll(async () => {
    // Desktop viewport so drag/snap tests have room to exercise positioning.
    await page.viewport(1400, 900);
    await worker.start({onUnhandledRequest: 'bypass'});
});

afterEach(() => {
    worker.resetHandlers();
});

afterAll(() => {
    worker.stop();
});

export {worker};
