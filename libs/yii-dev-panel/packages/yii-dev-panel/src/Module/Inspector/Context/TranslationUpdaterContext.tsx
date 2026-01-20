import {createContext, PropsWithChildren} from 'react';

type State = {
    updater: (category: string, locale: string, translation: string, message: string) => void;
};

const initialState: State = {
    updater: () => null,
};

export const TranslationUpdaterContext = createContext(initialState);
export const TranslationUpdaterContextProvider = ({updater, children}: PropsWithChildren<State>) => {
    return <TranslationUpdaterContext.Provider value={{updater}}>{children}</TranslationUpdaterContext.Provider>;
};
