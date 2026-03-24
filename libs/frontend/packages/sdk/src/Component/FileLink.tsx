import {parseFilePath, parseFilePathWithLineAnchor} from '@app-dev-panel/sdk/Helper/filePathParser';
import {useEditorUrl} from '@app-dev-panel/sdk/Helper/useEditorUrl';
import {Code} from '@mui/icons-material';
import {IconButton, Tooltip} from '@mui/material';
import {type ReactNode} from 'react';

type FileLinkProps = {
    /** File path, optionally with :line suffix (e.g. "/app/src/Foo.php:42") */
    path?: string;
    /** Class name for class-based resolution */
    className?: string;
    /** Method name (used with className) */
    methodName?: string;
    /** Explicit line number (overrides line parsed from path) */
    line?: number;
    /** Content to render as the link text. If not provided, uses path or className. */
    children?: ReactNode;
    /** Custom sx for the wrapper */
    sx?: Record<string, unknown>;
};

function extractLineFromPath(path: string): number | undefined {
    const match = path.match(/[#:](\d+)(?:-\d+)?$/);
    return match ? Number(match[1]) : undefined;
}

/**
 * Renders a link to the internal File Explorer with an optional "Open in Editor" button.
 * The editor button appears only when an editor is configured in settings.
 */
export const FileLink = ({path, className, methodName, line, children, sx}: FileLinkProps) => {
    const getEditorUrl = useEditorUrl();

    // Build the internal file explorer href
    let explorerHref: string;
    if (className) {
        const params = new URLSearchParams({class: className});
        if (methodName) params.set('method', methodName);
        explorerHref = `/inspector/files?${params.toString()}`;
    } else if (path) {
        explorerHref = `/inspector/files?path=${parseFilePathWithLineAnchor(path)}`;
    } else {
        return null;
    }

    // Build editor URL
    const cleanPath = path ? parseFilePath(path) : undefined;
    const resolvedLine = line ?? (path ? extractLineFromPath(path) : undefined);
    const editorUrl = cleanPath ? getEditorUrl(cleanPath, resolvedLine) : null;

    return (
        <span style={{display: 'inline-flex', alignItems: 'center', gap: 2, ...sx}}>
            {children !== undefined ? (
                <a href={explorerHref} style={{color: 'inherit', textDecoration: 'inherit'}}>
                    {children}
                </a>
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
