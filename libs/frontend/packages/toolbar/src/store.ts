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
import {FLUSH, PAUSE, PERSIST, PURGE, REGISTER, REHYDRATE, persistStore} from 'redux-persist';
import {initMessageListener} from 'redux-state-sync';

// TODO: get reducers and middlewares from modules.ts
const rootReducer = combineReducers({...ToolbarApiReducers, ...DebugReducers, ...ApplicationReducers});

export const createStore = (preloadedState: Partial<ReturnType<typeof rootReducer>> = {}) => {
    const store = configureStore({
        reducer: rootReducer,
        middleware: (getDefaultMiddleware) =>
            getDefaultMiddleware({
                serializableCheck: {ignoredActions: [FLUSH, REHYDRATE, PAUSE, PERSIST, PURGE, REGISTER]},
            })
                // .concat(consoleLogActionsMiddleware)
                .concat([...ApplicationMiddlewares, ...ToolbarApiMiddlewares, ...DebugMiddlewares]),
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
