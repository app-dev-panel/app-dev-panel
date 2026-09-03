import {preloadedApplicationState} from '@app-dev-panel/sdk/API/Application/api';
import {ApplicationSlice} from '@app-dev-panel/sdk/API/Application/ApplicationContext';
import {describe, expect, it} from 'vitest';

describe('preloadedApplicationState', () => {
    it('fills every slice field from the initial state and applies overrides', () => {
        const state = preloadedApplicationState({baseUrl: 'http://api.test', favoriteUrls: ['http://a']});

        expect(state.baseUrl).toBe('http://api.test');
        expect(state.favoriteUrls).toEqual(['http://a']);
        // Fields that used to be `undefined` until rehydration.
        expect(state.autoLatest).toBe(ApplicationSlice.getInitialState().autoLatest);
        expect(state.toolbarPosition).toBe('bottom');
        expect(state.iframeHeight).toBe(400);
    });

    it('leaves rehydration to redux-persist by not pre-filling _persist', () => {
        // A preloaded `_persist` makes persistReducer skip getStoredState(),
        // so nothing would ever be restored from or written to localStorage.
        expect(preloadedApplicationState()).not.toHaveProperty('_persist');
    });

    it('does not mutate the slice initial state', () => {
        const before = {...ApplicationSlice.getInitialState()};
        preloadedApplicationState({baseUrl: 'http://x'});
        expect(ApplicationSlice.getInitialState()).toEqual(before);
    });
});
