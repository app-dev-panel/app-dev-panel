import {combineReducers, configureStore} from '@reduxjs/toolkit';
import {setupListeners} from '@reduxjs/toolkit/query';
import {
    middlewares as ApplicationMiddlewares,
    reducers as ApplicationReducers,
} from '@yiisoft/yii-dev-panel-sdk/API/Application/api';
import {middlewares as DebugMiddlewares, reducers as DebugReducers} from '@yiisoft/yii-dev-panel-sdk/API/Debug/api';
import {
    middlewares as ToolbarApiMiddlewares,
    reducers as ToolbarApiReducers,
} from '@yiisoft/yii-dev-toolbar/Module/Toolbar/api';
import {TypedUseSelectorHook, useSelector} from 'react-redux';
import {FLUSH, PAUSE, PERSIST, PURGE, REGISTER, REHYDRATE, persistStore} from 'redux-persist';
import type {PreloadedStateShapeFromReducersMapObject} from 'redux';
import {initMessageListener} from 'redux-state-sync';

// TODO: get reducers and middlewares from modules.ts
const rootReducer = combineReducers({
    ...ToolbarApiReducers,
    ...DebugReducers,
    ...ApplicationReducers,
});

export const createStore = (preloadedState: PreloadedStateShapeFromReducersMapObject<typeof rootReducer>) => {
    const store = configureStore({
        reducer: rootReducer,
        middleware: (getDefaultMiddleware) =>
            getDefaultMiddleware({
                serializableCheck: {
                    ignoredActions: [FLUSH, REHYDRATE, PAUSE, PERSIST, PURGE, REGISTER],
                },
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
