import {
    buildEditorUrl,
    defaultEditorConfig,
    type EditorConfig,
    type EditorPreset,
} from '@app-dev-panel/sdk/Helper/editorUrl';
import {useCallback} from 'react';
import {useSelector} from 'react-redux';

/**
 * Returns a function that produces an `href` pointing at the configured IDE
 * for a given `(filePath, line)` pair.
 *
 * If the user has not yet picked an editor (config.editor === 'none') and a
 * `defaultPreset` is provided, that preset is used as a fallback. This lets
 * the toolbar hand out IDE links by default (e.g. `phpstorm://`) without
 * changing the panel's behaviour, where 'none' means "just link to the file
 * explorer".
 */
export function useEditorUrl(defaultPreset?: EditorPreset): (filePath: string, line?: number) => string | null {
    const editorConfig = useSelector(
        (state: {application: {editorConfig?: EditorConfig}}) => state.application.editorConfig,
    );
    const config = editorConfig ?? defaultEditorConfig;
    const effectiveConfig =
        config.editor === 'none' && defaultPreset && defaultPreset !== 'none'
            ? {...config, editor: defaultPreset}
            : config;

    return useCallback(
        (filePath: string, line?: number) => buildEditorUrl(effectiveConfig, filePath, line),
        [effectiveConfig],
    );
}
