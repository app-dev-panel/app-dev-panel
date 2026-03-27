import {GenCodeFile} from '@app-dev-panel/panel/Module/GenCode/Types/FIle.types';
import {GenCodeResult} from '@app-dev-panel/panel/Module/GenCode/Types/Result.types';
import {createAction, createReducer} from '@reduxjs/toolkit';
import React, {createContext, PropsWithChildren} from 'react';
import {FieldValues} from 'react-hook-form';

type State = {
    files: GenCodeFile[];
    operations: Record<string, string>;
    parameters: FieldValues;
    results: GenCodeResult[];
};

type ContextValue = State & {
    setFiles: (files: GenCodeFile[]) => void;
    setOperations: (operations: Record<string, string>) => void;
    setParameters: (parameters: FieldValues) => void;
    setResults: (results: GenCodeResult[]) => void;
    reset: () => void;
};

const initialState: State = {files: [], operations: {}, parameters: {}, results: []};

const setFiles = createAction<GenCodeFile[]>('setFiles');
const setOperations = createAction<Record<string, string>>('setOperations');
const setParameters = createAction<FieldValues>('setParameters');
const setResults = createAction<GenCodeResult[]>('setResults');
const reset = createAction<void>('reset');

export const Reducer = createReducer(initialState, (builder) => {
    builder
        .addCase(setFiles, (state, action) => {
            state.files = action.payload;
        })
        .addCase(setOperations, (state, action) => {
            state.operations = action.payload;
        })
        .addCase(setParameters, (state, action) => {
            state.parameters = action.payload;
        })
        .addCase(setResults, (state, action) => {
            state.results = action.payload;
        })
        .addCase(reset, (state) => {
            state.results = initialState.results;
            state.parameters = initialState.parameters;
            state.operations = initialState.operations;
            state.files = initialState.files;
        });
});

const initialContextValue: ContextValue = {
    ...initialState,
    setFiles: () => {},
    setOperations: () => {},
    setParameters: () => {},
    setResults: () => {},
    reset: () => {},
};

export const Context = createContext<ContextValue>(initialContextValue);
export const ContextProvider = ({children}: PropsWithChildren) => {
    const [state, dispatch] = React.useReducer(Reducer, initialState);

    const value: ContextValue = {
        parameters: state.parameters,
        setParameters: (parameters: FieldValues) => {
            dispatch(setParameters(parameters));
        },
        files: state.files,
        setFiles: (files: GenCodeFile[]) => {
            dispatch(setFiles(files));
        },
        operations: state.operations,
        setOperations: (operations: Record<string, string>) => {
            dispatch(setOperations(operations));
        },
        results: state.results,
        setResults: (results: GenCodeResult[]) => {
            dispatch(setResults(results));
        },
        reset: () => {
            dispatch(reset());
        },
    };

    return <Context.Provider value={value}>{children}</Context.Provider>;
};
