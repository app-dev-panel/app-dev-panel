import {type EditorConfig, buildEditorUrl, defaultEditorConfig} from '@app-dev-panel/sdk/Helper/editorUrl';
import {useCallback} from 'react';
import {useSelector} from 'react-redux';

export function useEditorUrl(): (filePath: string, line?: number) => string | null {
    const editorConfig = useSelector(
        (state: {application: {editorConfig?: EditorConfig}}) => state.application.editorConfig,
    );
    const config = editorConfig ?? defaultEditorConfig;

    return useCallback((filePath: string, line?: number) => buildEditorUrl(config, filePath, line), [config]);
}
