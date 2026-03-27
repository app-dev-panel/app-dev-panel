import {createAction, createReducer} from '@reduxjs/toolkit';
import React, {createContext, PropsWithChildren, useContext, useEffect, useMemo} from 'react';

const setItems = createAction<Breadcrumb[]>('setItems');

type Breadcrumb = {title: string; href: string} | string | null;
type State = {breadcrumbs: Breadcrumb[]; setBreadcrumbs: (items: Breadcrumb[]) => void};

const initialState: State = {breadcrumbs: [], setBreadcrumbs: () => {}};
export const Reducer = createReducer(initialState, (builder) => {
    builder.addCase(setItems, (state, {payload}) => {
        state.breadcrumbs = payload;
    });
});

export const BreadcrumbsContext = createContext(initialState);
export const useBreadcrumbsContext = () => useContext(BreadcrumbsContext);

export const useBreadcrumbs = (breadcrumbs: () => Breadcrumb[]) => {
    const context = useContext(BreadcrumbsContext);
    useEffect(() => {
        context.setBreadcrumbs(breadcrumbs());
        return () => {
            context.setBreadcrumbs([]);
        };
    }, [breadcrumbs, context]);
};
export const BreadcrumbsContextProvider = ({children}: PropsWithChildren) => {
    const [state, dispatch] = React.useReducer(Reducer, initialState);

    const value = useMemo(
        () =>
            ({
                breadcrumbs: state.breadcrumbs,
                setBreadcrumbs: (items: Breadcrumb[]) => {
                    dispatch(setItems(items));
                },
            }) satisfies State,
        [state.breadcrumbs],
    );

    return <BreadcrumbsContext.Provider value={value}>{children}</BreadcrumbsContext.Provider>;
};
