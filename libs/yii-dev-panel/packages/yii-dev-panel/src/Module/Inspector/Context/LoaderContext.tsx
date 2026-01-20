import {createContext, PropsWithChildren} from 'react';

type State = {
    loader: (objectId: string) => void;
};

const initialState: State = {
    loader: () => null,
};

export const LoaderContext = createContext(initialState);
export const LoaderContextProvider = ({loader, children}: PropsWithChildren<State>) => {
    return <LoaderContext.Provider value={{loader}}>{children}</LoaderContext.Provider>;
};
