import {useGetClassQuery} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {type EditorConfig} from '@app-dev-panel/sdk/Helper/editorUrl';
import {useEditorUrl} from '@app-dev-panel/sdk/Helper/useEditorUrl';
import {Code, FolderOpen} from '@mui/icons-material';
import {IconButton, Tooltip, Typography} from '@mui/material';
import {type ReactNode} from 'react';
import {useSelector} from 'react-redux';
import {Link as RouterLink} from 'react-router';

type ClassNameProps = {value: string; methodName?: string; children?: ReactNode; sx?: Record<string, unknown>};

const isFqcn = (value: string): boolean => value.includes('\\');

const isEditorEnabled = (config: EditorConfig | undefined): boolean =>
    !!config && (config.editor !== 'none' || !!config.customUrlTemplate);

/**
 * Renders a PHP class name with two action buttons:
 * - Open in File Explorer (internal `/inspector/files?class=...`)
 * - Open in Editor (e.g. `phpstorm://`, `vscode://`, resolved via user settings)
 *
 * The source file path is resolved lazily via the inspector API when an
 * editor is configured, so the editor URL can jump to the class (and the
 * method start line when `methodName` is provided).
 *
 * Buttons are suppressed when `value` is not a fully-qualified name
 * (no backslash) — short/unknown identifiers render as plain text.
 */
export const ClassName = ({value, methodName, children, sx}: ClassNameProps) => {
    const getEditorUrl = useEditorUrl();
    const editorConfig = useSelector(
        (state: {application: {editorConfig?: EditorConfig}}) => state.application.editorConfig,
    );
    const fqcn = isFqcn(value);
    const editorEnabled = isEditorEnabled(editorConfig);

    const {data} = useGetClassQuery({className: value, methodName: methodName ?? ''}, {skip: !fqcn || !editorEnabled});

    // getClass returns a single file-content object when a class is resolved,
    // despite the builder's declared `InspectorFile[]` return type.
    const resolved = !Array.isArray(data) ? (data as {path?: string; startLine?: number} | undefined) : undefined;
    const editorUrl = resolved?.path ? getEditorUrl(resolved.path, resolved.startLine) : null;

    const params = new URLSearchParams({class: value});
    if (methodName) params.set('method', methodName);
    const explorerHref = `/inspector/files?${params.toString()}`;

    const label = children ?? (
        <Typography
            component="span"
            sx={(theme) => ({fontFamily: theme.adp.fontFamilyMono, fontSize: 'inherit', color: 'inherit'})}
        >
            {value}
        </Typography>
    );

    if (!fqcn) {
        return <span style={{display: 'inline-flex', alignItems: 'center', minWidth: 0, ...sx}}>{label}</span>;
    }

    return (
        <span style={{display: 'inline-flex', alignItems: 'center', gap: 2, minWidth: 0, ...sx}}>
            {label}
            {editorUrl && (
                <Tooltip title="Open in Editor">
                    <IconButton
                        size="small"
                        component="a"
                        href={editorUrl}
                        aria-label="Open in Editor"
                        onClick={(e) => e.stopPropagation()}
                        sx={{p: 0.25, ml: 0.25}}
                    >
                        <Code sx={{fontSize: 14}} />
                    </IconButton>
                </Tooltip>
            )}
            <Tooltip title="Open in File Explorer">
                <IconButton
                    size="small"
                    component={RouterLink}
                    to={explorerHref}
                    aria-label="Open in File Explorer"
                    onClick={(e) => e.stopPropagation()}
                    sx={{p: 0.25, ml: editorUrl ? 0 : 0.25}}
                >
                    <FolderOpen sx={{fontSize: 14}} />
                </IconButton>
            </Tooltip>
        </span>
    );
};
