import {createSlice, PayloadAction} from '@reduxjs/toolkit';

type ApplicationContext = {
    baseUrl: string;
    preferredPageSize: number;
    toolbarOpen: boolean;
    favoriteUrls: string[];
    autoLatest: boolean;
    iframeHeight: number;
};
export const ApplicationSlice = createSlice({
    name: 'application',
    initialState: {
        baseUrl: '',
        preferredPageSize: 20,
        toolbarOpen: true,
        favoriteUrls: [] as string[],
        autoLatest: false,
        iframeHeight: 400,
    } as ApplicationContext,
    reducers: {
        changeBaseUrl(state, action: PayloadAction<string>) {
            state.baseUrl = action.payload;
        },
        setToolbarOpen(state, action: PayloadAction<boolean>) {
            state.toolbarOpen = action.payload;
        },
        setPreferredPageSize(state, action: PayloadAction<number>) {
            state.preferredPageSize = action.payload;
        },
        addFavoriteUrl(state, action: PayloadAction<string>) {
            const set = new Set(state.favoriteUrls);
            state.favoriteUrls = Array.from(set.add(action.payload).values());
        },
        removeFavoriteUrl(state, action: PayloadAction<string>) {
            const set = new Set(state.favoriteUrls);
            set.delete(action.payload);
            state.favoriteUrls = Array.from(set.values());
        },
        changeAutoLatest: (state, action) => {
            state.autoLatest = action.payload;
        },
        setIFrameHeight: (state, action) => {
            state.iframeHeight = action.payload;
        },
    },
});

export const {
    changeBaseUrl,
    changeAutoLatest,
    setToolbarOpen,
    setPreferredPageSize,
    addFavoriteUrl,
    removeFavoriteUrl,
    setIFrameHeight,
} = ApplicationSlice.actions;
