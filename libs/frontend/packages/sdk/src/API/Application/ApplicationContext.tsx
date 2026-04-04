import {type EditorConfig, type EditorPreset, defaultEditorConfig} from '@app-dev-panel/sdk/Helper/editorUrl';
import {createSlice, PayloadAction} from '@reduxjs/toolkit';

export type ToolbarPosition = 'float' | 'bottom' | 'right' | 'left';

type ApplicationContext = {
    baseUrl: string;
    preferredPageSize: number;
    toolbarOpen: boolean;
    toolbarPosition: ToolbarPosition;
    toolbarFloatRect: {x: number; y: number; width: number; height: number} | null;
    favoriteUrls: string[];
    autoLatest: boolean;
    iframeHeight: number;
    selectedService: string;
    themeMode: 'light' | 'dark' | 'system';
    showInactiveCollectors: boolean;
    editorConfig: EditorConfig;
};
export const ApplicationSlice = createSlice({
    name: 'application',
    initialState: {
        baseUrl: '',
        preferredPageSize: 20,
        toolbarOpen: true,
        toolbarPosition: 'bottom' as ToolbarPosition,
        toolbarFloatRect: null as {x: number; y: number; width: number; height: number} | null,
        favoriteUrls: [] as string[],
        autoLatest: false,
        iframeHeight: 400,
        selectedService: 'local',
        themeMode: 'system',
        showInactiveCollectors: false,
        editorConfig: defaultEditorConfig,
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
        setToolbarPosition: (state, action: PayloadAction<ToolbarPosition>) => {
            state.toolbarPosition = action.payload;
        },
        setToolbarFloatRect: (
            state,
            action: PayloadAction<{x: number; y: number; width: number; height: number} | null>,
        ) => {
            state.toolbarFloatRect = action.payload;
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
        changeEditorPreset: (state, action: PayloadAction<EditorPreset>) => {
            state.editorConfig = {...(state.editorConfig ?? defaultEditorConfig), editor: action.payload};
        },
        changeEditorCustomTemplate: (state, action: PayloadAction<string>) => {
            state.editorConfig = {...(state.editorConfig ?? defaultEditorConfig), customUrlTemplate: action.payload};
        },
        changeEditorPathMapping: (state, action: PayloadAction<Record<string, string>>) => {
            state.editorConfig = {...(state.editorConfig ?? defaultEditorConfig), pathMapping: action.payload};
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
    setToolbarPosition,
    setToolbarFloatRect,
    changeSelectedService,
    changeThemeMode,
    changeShowInactiveCollectors,
    changeEditorPreset,
    changeEditorCustomTemplate,
    changeEditorPathMapping,
} = ApplicationSlice.actions;
