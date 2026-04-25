import {parseFilePath, parseFilePathWithLineAnchor} from '@app-dev-panel/sdk/Helper/filePathParser';
import {useEditorUrl} from '@app-dev-panel/sdk/Helper/useEditorUrl';
import {Code} from '@mui/icons-material';
import {IconButton, Tooltip} from '@mui/material';
import {type ReactNode} from 'react';
import {Link as RouterLink} from 'react-router';

type FileLinkProps = {
    /** File path, optionally with :line suffix (e.g. "/app/src/Foo.php:42") */
    path: string;
    /** Explicit line number (overrides line parsed from path) */
    line?: number;
    /** Content to render as the link text. If not provided, no text is rendered. */
    children?: ReactNode;
    /** Custom sx for the wrapper */
    sx?: Record<string, unknown>;
};

function extractLineFromPath(path: string): number | undefined {
    const match = path.match(/[#:](\d+)(?:-\d+)?$/);
    return match ? Number(match[1]) : undefined;
}

/**
 * Renders a link to the internal File Explorer for a given file path, with an
 * optional "Open in Editor" button. The editor button appears only when an
 * editor is configured in settings.
 *
 * For PHP class names (FQCN) use `ClassName` instead — it resolves the source
 * location via the inspector API and adds a dedicated File Explorer button.
 *
 * Uses React Router Link directly (not MUI Link) to ensure SPA navigation
 * even when rendered inside components that override the MUI theme (e.g. JsonViewer).
 */
export const FileLink = ({path, line, children, sx}: FileLinkProps) => {
    const getEditorUrl = useEditorUrl();

    if (!path) {
        return null;
    }

    const explorerHref = `/inspector/files?path=${parseFilePathWithLineAnchor(path)}`;
    const cleanPath = parseFilePath(path);
    const resolvedLine = line ?? extractLineFromPath(path);
    const editorUrl = getEditorUrl(cleanPath, resolvedLine);

    return (
        <span style={{display: 'inline-flex', alignItems: 'center', gap: 2, ...sx}}>
            {children !== undefined ? (
                <RouterLink
                    to={explorerHref}
                    style={{color: 'inherit', textDecoration: 'inherit'}}
                    onClick={(e) => e.stopPropagation()}
                >
                    {children}
                </RouterLink>
            ) : null}
            {editorUrl && (
                <Tooltip title="Open in Editor">
                    <IconButton
                        size="small"
                        component="a"
                        href={editorUrl}
                        aria-label="Open in Editor"
                        onClick={(e: React.MouseEvent) => e.stopPropagation()}
                        sx={{p: 0.25, ml: 0.25}}
                    >
                        <Code sx={{fontSize: 14}} />
                    </IconButton>
                </Tooltip>
            )}
        </span>
    );
};
