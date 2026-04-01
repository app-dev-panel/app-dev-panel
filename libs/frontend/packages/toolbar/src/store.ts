import {
    middlewares as ApplicationMiddlewares,
    reducers as ApplicationReducers,
} from '@app-dev-panel/sdk/API/Application/api';
import {middlewares as DebugMiddlewares, reducers as DebugReducers} from '@app-dev-panel/sdk/API/Debug/api';
import {
    middlewares as ToolbarApiMiddlewares,
    reducers as ToolbarApiReducers,
} from '@app-dev-panel/toolbar/Module/Toolbar/api';
import {combineReducers, configureStore} from '@reduxjs/toolkit';
import {setupListeners} from '@reduxjs/toolkit/query';
import {TypedUseSelectorHook, useSelector} from 'react-redux';
import {FLUSH, PAUSE, PERSIST, persistStore, PURGE, REGISTER, REHYDRATE} from 'redux-persist';
import {initMessageListener} from 'redux-state-sync';

const rootReducer = combineReducers({...ToolbarApiReducers, ...DebugReducers, ...ApplicationReducers});

export const createStore = (preloadedState: Partial<ReturnType<typeof rootReducer>> = {}, forcedBaseUrl?: string) => {
    // Patch persisted application state so redux-persist REHYDRATE
    // uses the correct baseUrl instead of a stale persisted value.
    if (forcedBaseUrl) {
        try {
            const raw = localStorage.getItem('persist:application');
            if (raw) {
                const parsed = JSON.parse(raw);
                parsed.baseUrl = JSON.stringify(forcedBaseUrl);
                localStorage.setItem('persist:application', JSON.stringify(parsed));
            }
        } catch {
            // localStorage may be unavailable or corrupted
        }
    }

    const store = configureStore({
        reducer: rootReducer,
        middleware: (getDefaultMiddleware) =>
            getDefaultMiddleware({
                serializableCheck: {ignoredActions: [FLUSH, REHYDRATE, PAUSE, PERSIST, PURGE, REGISTER]},
            }).concat([...ApplicationMiddlewares, ...ToolbarApiMiddlewares, ...DebugMiddlewares]),
        devTools: import.meta.env.DEV,
        preloadedState: preloadedState,
    });
    setupListeners(store.dispatch);
    initMessageListener(store);

    const persistor = persistStore(store);

    return {store, persistor};
};

type createStoreFunction = typeof createStore;
type ReturnTypeOfCreateStoreFunction = ReturnType<createStoreFunction>;
type StoreType = ReturnTypeOfCreateStoreFunction['store'];

export type RootState = ReturnType<StoreType['getState']>;
export type AppDispatch = StoreType['dispatch'];
const useAppSelector: TypedUseSelectorHook<RootState> = useSelector<RootState>;

export {useAppSelector as useSelector};
