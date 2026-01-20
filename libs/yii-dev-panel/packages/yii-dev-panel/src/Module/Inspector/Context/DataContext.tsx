import {createAction, createReducer} from '@reduxjs/toolkit';
import React, {createContext, PropsWithChildren, useMemo} from 'react';

const setObjects = createAction<any>('setObjects');
const insertObject = createAction<{id: string; object: any}>('insertObject');

type ObjectType = {
    id: string;
    value: any;
};
type State = {
    objects: ObjectType[];
    insertObject: any;
    setObjects: any;
};

const initialState: State = {
    objects: [],
    insertObject: insertObject,
    setObjects: setObjects,
};
export const Reducer = createReducer(initialState, (builder) => {
    builder
        .addCase(setObjects, (state, {payload}) => {
            state.objects = payload;
        })
        .addCase(insertObject, (state, {payload: {id, object}}) => {
            state.objects = state.objects.map((value) => (id === value.id ? {id: id, value: object} : value));
        });
});

export const DataContext = createContext(initialState);
export const DataContextProvider = ({children}: PropsWithChildren) => {
    const [state, dispatch] = React.useReducer(Reducer, initialState);

    const value = useMemo(
        () => ({
            objects: state.objects,
            setObjects: (parameters: Record<string, any[]>) => {
                dispatch(setObjects(parameters));
            },
            insertObject: (id: string, object: any) => {
                dispatch(insertObject({id, object}));
            },
        }),
        [state.objects],
    );

    return <DataContext.Provider value={value}>{children}</DataContext.Provider>;
};
