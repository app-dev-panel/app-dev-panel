import {createAction, createReducer} from '@reduxjs/toolkit';
import React, {createContext, PropsWithChildren, useContext, useEffect, useMemo} from 'react';

const setItems = createAction<Breadcrumb[]>('setItems');

type Breadcrumb =
    | {
          title: string;
          href: any;
      }
    | string
    | null;
type State = {
    breadcrumbs: Breadcrumb[];
    setBreadcrumbs: typeof setItems;
};

const initialState: State = {
    breadcrumbs: [],
    setBreadcrumbs: setItems,
};
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
        console.log('hook update', breadcrumbs);
        context.setBreadcrumbs(breadcrumbs());
    }, []);
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
            }) as State,
        [state.breadcrumbs],
    );

    return <BreadcrumbsContext.Provider value={value}>{children}</BreadcrumbsContext.Provider>;
};
