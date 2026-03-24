import {useBreadcrumbs} from '@app-dev-panel/panel/Application/Context/BreadcrumbsContext';
import {
    InspectorFile,
    InspectorFileContent,
    useLazyGetClassQuery,
    useLazyGetFilesQuery,
} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {TreeView} from '@app-dev-panel/panel/Module/Inspector/Component/TreeView/TreeView';
import {CodeHighlight} from '@app-dev-panel/sdk/Component/CodeHighlight';
import {SearchFilter, type SearchMatch} from '@app-dev-panel/sdk/Component/SearchFilter';
import {parseFilePath, parsePathLineAnchor} from '@app-dev-panel/sdk/Helper/filePathParser';
import {formatBytes} from '@app-dev-panel/sdk/Helper/formatBytes';
import {scrollToAnchor} from '@app-dev-panel/sdk/Helper/scrollToAnchor';
import {useEditorUrl} from '@app-dev-panel/sdk/Helper/useEditorUrl';
import {Code, ContentCopy, FolderOpen, Lock, Person, Storage} from '@mui/icons-material';
import {Box, Breadcrumbs, IconButton, Link, Paper, Tooltip, Typography} from '@mui/material';
import {useCallback, useEffect, useLayoutEffect, useState} from 'react';
import {useSearchParams} from 'react-router-dom';

type PathBreadcrumbsProps = {onClick: (nodeId: string) => void; path: string};

const PathBreadcrumbs = ({path, onClick}: PathBreadcrumbsProps) => {
    const paths = path.split('/').filter((s) => !!s.length);
    const fullPath: string[] = [];

    useBreadcrumbs(() => ['Inspector', 'File Explorer']);

    return (
        <Breadcrumbs sx={{minWidth: 0}}>
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

type FileMetaBarProps = {file: InspectorFileContent};

const FileMetaBar = ({file}: FileMetaBarProps) => {
    const items = [
        {icon: <FolderOpen sx={{fontSize: 16}} />, label: `@root${file.directory}`},
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

    const [lazyGetFilesQuery, getFilesQueryInfo] = useLazyGetFilesQuery();
    const [lazyGetClassQuery, getClassQueryInfo] = useLazyGetClassQuery();
    const [tree, setTree] = useState<InspectorFile[]>([]);
    const [filteredTree, setFilteredTree] = useState<InspectorFile[]>([]);
    const [file, setFile] = useState<InspectorFileContent | null>(null);

    useEffect(() => {
        (async () => {
            const response =
                className !== ''
                    ? await lazyGetClassQuery({className, methodName})
                    : await lazyGetFilesQuery(parseFilePath(path));

            if (Array.isArray(response.data)) {
                const rows = sortTree(response.data);
                setTree(rows);
            } else {
                setFile(response.data as any);
            }
        })();
    }, [path, className]);

    const highlightLines: [number, number] | [number] | undefined = (() => {
        if (file?.startLine && file?.endLine) return [file.startLine, file.endLine];
        if (file?.startLine) return [file.startLine];
        return parsePathLineAnchor(window.location.hash);
    })();

    useLayoutEffect(() => {
        if (file) {
            const anchor = file.startLine ? `L${file.startLine}` : undefined;
            if (!anchor) {
                const lines = parsePathLineAnchor(window.location.hash);
                scrollToAnchor(25, lines && `L${lines[0]}`);
            } else {
                scrollToAnchor(25, anchor);
            }
        }
    }, [file]);

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
            {file && (
                <Box sx={{display: 'flex', flexDirection: 'column', gap: 2}}>
                    <Box sx={{display: 'flex', alignItems: 'center', gap: 1}}>
                        <PathBreadcrumbs path={file.path} onClick={handleBreadcrumbClick} />
                        <ActionButtons editorUrl={editorUrl} fullPath={file.path} />
                    </Box>

                    <FileMetaBar file={file} />

                    <Paper variant="outlined" sx={{overflow: 'hidden', borderRadius: 2}}>
                        <CodeHighlight
                            language={file.extension}
                            code={file.content}
                            highlightLines={highlightLines}
                            filePath={file.path}
                        />
                    </Paper>
                </Box>
            )}
            {!file && (
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

            {getClassQueryInfo.error &&
                'status' in getClassQueryInfo.error &&
                getClassQueryInfo.error.status === 404 && <Typography>File not found</Typography>}
        </>
    );
};
