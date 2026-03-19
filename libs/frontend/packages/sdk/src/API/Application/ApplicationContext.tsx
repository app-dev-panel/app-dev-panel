import {createSlice, PayloadAction} from '@reduxjs/toolkit';

type ApplicationContext = {
    baseUrl: string;
    preferredPageSize: number;
    toolbarOpen: boolean;
    favoriteUrls: string[];
    autoLatest: boolean;
    iframeHeight: number;
    selectedService: string;
    themeMode: 'light' | 'dark' | 'system';
    showInactiveCollectors: boolean;
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
        selectedService: 'local',
        themeMode: 'system',
        showInactiveCollectors: false,
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
        changeSelectedService(state, action: PayloadAction<string>) {
            state.selectedService = action.payload;
        },
        changeThemeMode: (state, action: PayloadAction<'light' | 'dark' | 'system'>) => {
            state.themeMode = action.payload;
        },
        changeShowInactiveCollectors: (state, action: PayloadAction<boolean>) => {
            state.showInactiveCollectors = action.payload;
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
    changeSelectedService,
    changeThemeMode,
    changeShowInactiveCollectors,
} = ApplicationSlice.actions;
