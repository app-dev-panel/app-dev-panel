import {
    InspectorFile,
    InspectorFileContent,
    useLazyGetClassQuery,
    useLazyGetFilesQuery,
} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {TreeView} from '@app-dev-panel/panel/Module/Inspector/Component/TreeView/TreeView';
import {CodeHighlight} from '@app-dev-panel/sdk/Component/CodeHighlight';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {SearchFilter, type SearchMatch} from '@app-dev-panel/sdk/Component/SearchFilter';
import {parseFilePath, parsePathLineAnchor} from '@app-dev-panel/sdk/Helper/filePathParser';
import {formatBytes} from '@app-dev-panel/sdk/Helper/formatBytes';
import {scrollToAnchor} from '@app-dev-panel/sdk/Helper/scrollToAnchor';
import {useEditorUrl} from '@app-dev-panel/sdk/Helper/useEditorUrl';
import {Code, ContentCopy, FolderOpen, Lock, Person, Storage} from '@mui/icons-material';
import {Alert, AlertTitle, Box, Breadcrumbs, IconButton, Link, Paper, Tooltip, Typography} from '@mui/material';
import {useCallback, useEffect, useLayoutEffect, useState} from 'react';
import {useSearchParams} from 'react-router';

type PathBreadcrumbsProps = {onClick: (nodeId: string) => void; path: string; insideRoot?: boolean};

const PathBreadcrumbs = ({path, onClick, insideRoot = true}: PathBreadcrumbsProps) => {
    const paths = path.split('/').filter((s) => !!s.length);
    const fullPath: string[] = [];

    return (
        <Breadcrumbs sx={{minWidth: 0}}>
            {insideRoot && (
                <Link
                    underline="hover"
                    color="inherit"
                    href={'#'}
                    onClick={() => {
                        onClick('/');
                        return false;
                    }}
                >
                    @root
                </Link>
            )}
            {paths.map((directory, index) => {
                fullPath.push(directory);
                const currentPath = '/' + fullPath.join('/');

                if (index === paths.length - 1) {
                    return (
                        <Typography key={index} color="text.primary" sx={{fontWeight: 600}}>
                            {directory}
                        </Typography>
                    );
                }

                if (!insideRoot) {
                    return (
                        <Typography key={index} color="text.secondary">
                            {directory}
                        </Typography>
                    );
                }

                return (
                    <Link
                        key={index}
                        underline="hover"
                        color="inherit"
                        href={'#'}
                        onClick={() => {
                            onClick(currentPath);
                            return false;
                        }}
                    >
                        {directory}
                    </Link>
                );
            })}
        </Breadcrumbs>
    );
};

type ActionButtonsProps = {editorUrl: string | null; fullPath: string; showCopy?: boolean};

const ActionButtons = ({editorUrl, fullPath, showCopy = true}: ActionButtonsProps) => {
    const [copied, setCopied] = useState(false);

    const handleCopy = useCallback(() => {
        navigator.clipboard.writeText(fullPath);
        setCopied(true);
        setTimeout(() => setCopied(false), 1500);
    }, [fullPath]);

    return (
        <Box sx={{display: 'flex', alignItems: 'center', flexShrink: 0}}>
            {showCopy && (
                <Tooltip title={copied ? 'Copied!' : 'Copy path'}>
                    <IconButton size="small" onClick={handleCopy} sx={{color: copied ? 'success.main' : undefined}}>
                        <ContentCopy sx={{fontSize: 16}} />
                    </IconButton>
                </Tooltip>
            )}
            {editorUrl && (
                <Tooltip title="Open in Editor">
                    <IconButton
                        size="small"
                        component="a"
                        href={editorUrl}
                        onClick={(e: React.MouseEvent) => e.stopPropagation()}
                        sx={{color: 'primary.main'}}
                    >
                        <Code sx={{fontSize: 16}} />
                    </IconButton>
                </Tooltip>
            )}
        </Box>
    );
};

type FileMetaBarProps = {file: InspectorFileContent; onDirectoryClick?: (path: string) => void};

const FileMetaBar = ({file, onDirectoryClick}: FileMetaBarProps) => {
    const insideRoot = file.insideRoot !== false;
    const items = [
        {icon: <Lock sx={{fontSize: 16}} />, label: file.permissions},
        {
            icon: <Person sx={{fontSize: 16}} />,
            label: `${file.user?.name ?? file.user.uid}:${file.group?.name ?? file.group.gid}`,
        },
        {icon: <Storage sx={{fontSize: 16}} />, label: formatBytes(file.size)},
    ];

    return (
        <Box
            sx={{display: 'flex', flexWrap: 'wrap', gap: 2.5, px: 2, py: 1.5, bgcolor: 'action.hover', borderRadius: 1}}
        >
            <Box sx={{display: 'flex', alignItems: 'center', gap: 0.75}}>
                <Box sx={{color: 'text.disabled', display: 'flex', alignItems: 'center'}}>
                    <FolderOpen sx={{fontSize: 16}} />
                </Box>
                {insideRoot ? (
                    <Link
                        component="button"
                        variant="body2"
                        underline="hover"
                        color="text.secondary"
                        onClick={() => onDirectoryClick?.(file.directory)}
                    >
                        @root{file.directory}
                    </Link>
                ) : (
                    <Typography variant="body2" color="text.secondary">
                        {file.directory}
                    </Typography>
                )}
            </Box>
            {items.map((item, i) => (
                <Box key={i} sx={{display: 'flex', alignItems: 'center', gap: 0.75}}>
                    <Box sx={{color: 'text.disabled', display: 'flex', alignItems: 'center'}}>{item.icon}</Box>
                    <Typography variant="body2" color="text.secondary">
                        {item.label}
                    </Typography>
                </Box>
            ))}
        </Box>
    );
};

function sortTree(data: InspectorFile[]) {
    return data.slice().sort((file1, file2) => {
        if (file1.path.endsWith('/') && !file2.path.endsWith('/')) {
            return file2.path.endsWith('/..') ? 1 : -1;
        }
        if (file2.path.endsWith('/') && !file1.path.endsWith('/')) {
            return file1.path.endsWith('/..') ? -1 : 1;
        }
        return file1.path.localeCompare(file2.path);
    });
}

export const FileExplorerPage = () => {
    const [searchParams, setSearchParams] = useSearchParams();
    const path = searchParams.get('path') || '/';
    const className = searchParams.get('class') || '';
    const methodName = searchParams.get('method') || '';
    const getEditorUrl = useEditorUrl();

    const [lazyGetFilesQuery, _getFilesQueryInfo] = useLazyGetFilesQuery();
    const [lazyGetClassQuery, _getClassQueryInfo] = useLazyGetClassQuery();
    const [tree, setTree] = useState<InspectorFile[]>([]);
    const [filteredTree, setFilteredTree] = useState<InspectorFile[]>([]);
    const [file, setFile] = useState<InspectorFileContent | null>(null);
    const [error, setError] = useState<{status: number; message: string} | null>(null);

    useEffect(() => {
        setError(null);
        (async () => {
            const response =
                className !== ''
                    ? await lazyGetClassQuery({className, methodName})
                    : await lazyGetFilesQuery(parseFilePath(path));

            if (response.error) {
                const err = response.error as any;
                const status = err?.status ?? err?.originalStatus ?? 0;
                const message = err?.data?.data?.message ?? err?.data?.message ?? 'Unknown error';
                setError({status, message});
                setTree([]);
                setFile(null);
                return;
            }

            if (Array.isArray(response.data)) {
                const rows = sortTree(response.data);
                setTree(rows);
            } else {
                setFile(response.data as any);
            }
        })();
    }, [path, className]);

    const [highlightLines, setHighlightLines] = useState<[number, number] | [number] | undefined>(() => {
        return parsePathLineAnchor(window.location.hash);
    });

    useLayoutEffect(() => {
        if (file) {
            const initial: [number, number] | [number] | undefined = (() => {
                if (file.startLine && file.endLine) return [file.startLine, file.endLine];
                if (file.startLine) return [file.startLine];
                return parsePathLineAnchor(window.location.hash);
            })();
            setHighlightLines(initial);
            const anchor = initial ? `L${initial[0]}` : undefined;
            scrollToAnchor(25, anchor);
        }
    }, [file]);

    const handleLineClick = useCallback(
        (lineNumber: number, shiftKey: boolean) => {
            let newHighlight: [number, number] | [number];
            if (shiftKey && highlightLines) {
                const anchor = highlightLines[0];
                const start = Math.min(anchor, lineNumber);
                const end = Math.max(anchor, lineNumber);
                newHighlight = start === end ? [start] : [start, end];
            } else {
                newHighlight = [lineNumber];
            }
            setHighlightLines(newHighlight);
            const hash = newHighlight.length === 2 ? `#L${newHighlight[0]}-${newHighlight[1]}` : `#L${newHighlight[0]}`;
            window.history.replaceState(null, '', hash);
            scrollToAnchor(25, `L${newHighlight[0]}`);
        },
        [highlightLines],
    );

    const changePath = (path: string) => {
        setSearchParams({path});
    };

    const getFileSearchText = useCallback((f: InspectorFile) => f.baseName, []);
    const handleFilterChange = useCallback((results: SearchMatch<InspectorFile>[]) => {
        setFilteredTree(results.map((r) => r.item));
    }, []);

    const editorUrl = file ? getEditorUrl(file.path, file.startLine) : null;
    const directoryEditorUrl = !file ? getEditorUrl(path) : null;

    const handleBreadcrumbClick = (clickedPath: string) => {
        if (file) {
            setFile(null);
        }
        changePath(clickedPath);
    };

    return (
        <>
            <PageHeader
                title="File Explorer"
                icon="folder_open"
                description="Browse application files and source code"
            />
            {error && (
                <Box sx={{display: 'flex', flexDirection: 'column', gap: 2}}>
                    <Box sx={{display: 'flex', alignItems: 'center', gap: 1}}>
                        <PathBreadcrumbs path={path} onClick={handleBreadcrumbClick} />
                    </Box>
                    <Alert severity="error">
                        <AlertTitle>
                            {error.status === 403
                                ? 'Access denied'
                                : error.status === 404
                                  ? 'Not found'
                                  : `Error ${error.status}`}
                        </AlertTitle>
                        {error.message}
                    </Alert>
                </Box>
            )}
            {!error && file && (
                <Box sx={{display: 'flex', flexDirection: 'column', gap: 2}}>
                    <Box sx={{display: 'flex', alignItems: 'center', gap: 1}}>
                        <PathBreadcrumbs
                            path={file.path}
                            onClick={handleBreadcrumbClick}
                            insideRoot={file.insideRoot !== false}
                        />
                        <ActionButtons editorUrl={editorUrl} fullPath={file.path} />
                    </Box>

                    <FileMetaBar file={file} onDirectoryClick={handleBreadcrumbClick} />

                    <Paper variant="outlined" sx={{overflow: 'hidden', borderRadius: 2}}>
                        <CodeHighlight
                            language={file.extension}
                            code={file.content}
                            highlightLines={highlightLines}
                            onLineClick={handleLineClick}
                        />
                    </Paper>
                </Box>
            )}
            {!error && !file && (
                <Box sx={{display: 'flex', flexDirection: 'column', gap: 2}}>
                    <Box sx={{display: 'flex', alignItems: 'center', gap: 1}}>
                        <PathBreadcrumbs path={path} onClick={handleBreadcrumbClick} />
                        <ActionButtons editorUrl={directoryEditorUrl} fullPath={path} showCopy={path !== '/'} />
                        <Box sx={{ml: 'auto'}}>
                            <SearchFilter
                                items={tree}
                                getSearchText={getFileSearchText}
                                placeholder="Filter files..."
                                onChange={handleFilterChange}
                            />
                        </Box>
                    </Box>
                    <Paper variant="outlined" sx={{borderRadius: 2}}>
                        <TreeView tree={filteredTree} onSelect={changePath} />
                    </Paper>
                </Box>
            )}
        </>
    );
};
