import {createReducer} from '@reduxjs/toolkit';
import React, {createContext} from 'react';

type State = {
    baseUrl: string;
    openLinksInNewWindow: boolean;
};

const initialState: State = {
    baseUrl: '',
    openLinksInNewWindow: false,
};

const Reducer = createReducer(initialState, (builder) => {});

export const RouterOptionsContext = createContext(initialState);
type ContextProviderProps = {
    baseUrl: string;
    openLinksInNewWindow: boolean;
};
export const RouterOptionsContextProvider = ({
    children,
    baseUrl,
    openLinksInNewWindow,
}: React.PropsWithChildren<ContextProviderProps>) => {
    const [state, dispatch] = React.useReducer(Reducer, initialState);

    const value = {
        baseUrl: baseUrl,
        openLinksInNewWindow: openLinksInNewWindow,
    };

    return <RouterOptionsContext.Provider value={value}>{children}</RouterOptionsContext.Provider>;
};
