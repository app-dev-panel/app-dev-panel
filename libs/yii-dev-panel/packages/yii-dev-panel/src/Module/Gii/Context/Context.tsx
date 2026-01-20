import {createAction, createReducer} from '@reduxjs/toolkit';
import {GiiFile} from '@yiisoft/yii-dev-panel/Module/Gii/Types/FIle.types';
import {GiiResult} from '@yiisoft/yii-dev-panel/Module/Gii/Types/Result.types';
import React, {createContext} from 'react';

type State = {
    files: GiiFile[];
    operations: any[];
    parameters: any[];
    results: GiiResult[];
};

const initialState: State = {
    files: [],
    operations: [],
    parameters: [],
    results: [],
};

const setFiles = createAction<any>('setFiles');
const setOperations = createAction<any>('setOperations');
const setParameters = createAction<any>('setParameters');
const setResults = createAction<any>('setResults');
const reset = createAction<void>('reset');

export const Reducer = createReducer(initialState, (builder) => {
    builder
        .addCase(setFiles, (state, action) => {
            state.files = action.payload as any;
        })
        .addCase(setOperations, (state, action) => {
            state.operations = action.payload as any;
        })
        .addCase(setParameters, (state, action) => {
            state.parameters = action.payload as any;
        })
        .addCase(setResults, (state, action) => {
            state.results = action.payload as any;
        })
        .addCase(reset, (state, action) => {
            state.results = initialState.results;
            state.parameters = initialState.parameters;
            state.operations = initialState.operations;
            state.files = initialState.files;
        });
});

export const Context = createContext(initialState);
export const ContextProvider = ({children}: any) => {
    const [state, dispatch] = React.useReducer(Reducer, initialState);

    const value = {
        parameters: state.parameters,
        setParameters: (parameters: any[]) => {
            dispatch(setParameters(parameters));
        },
        files: state.files,
        setFiles: (files: any[]) => {
            dispatch(setFiles(files));
        },
        operations: state.operations,
        setOperations: (operations: any[]) => {
            dispatch(setOperations(operations));
        },
        results: state.results,
        setResults: (results: any[]) => {
            dispatch(setResults(results));
        },
        reset: () => {
            dispatch(reset());
        },
    };

    return <Context.Provider value={value}>{children}</Context.Provider>;
};
