import {GenCodeFile} from '@app-dev-panel/panel/Module/GenCode/Types/FIle.types';
import {GenCodeResult} from '@app-dev-panel/panel/Module/GenCode/Types/Result.types';
import {createAction, createReducer} from '@reduxjs/toolkit';
import React, {createContext, PropsWithChildren, useCallback, useMemo} from 'react';
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

    const setFilesCallback = useCallback((files: GenCodeFile[]) => dispatch(setFiles(files)), []);
    const setOperationsCallback = useCallback(
        (operations: Record<string, string>) => dispatch(setOperations(operations)),
        [],
    );
    const setParametersCallback = useCallback((parameters: FieldValues) => dispatch(setParameters(parameters)), []);
    const setResultsCallback = useCallback((results: GenCodeResult[]) => dispatch(setResults(results)), []);
    const resetCallback = useCallback(() => dispatch(reset()), []);

    const value = useMemo<ContextValue>(
        () => ({
            ...state,
            setFiles: setFilesCallback,
            setOperations: setOperationsCallback,
            setParameters: setParametersCallback,
            setResults: setResultsCallback,
            reset: resetCallback,
        }),
        [state, setFilesCallback, setOperationsCallback, setParametersCallback, setResultsCallback, resetCallback],
    );

    return <Context.Provider value={value}>{children}</Context.Provider>;
};
