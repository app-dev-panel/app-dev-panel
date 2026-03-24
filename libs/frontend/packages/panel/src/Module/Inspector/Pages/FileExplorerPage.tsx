import {useBreadcrumbs} from '@app-dev-panel/panel/Application/Context/BreadcrumbsContext';
import {
    InspectorFile,
    InspectorFileContent,
    useLazyGetClassQuery,
    useLazyGetFilesQuery,
} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {TreeView} from '@app-dev-panel/panel/Module/Inspector/Component/TreeView/TreeView';
import {CodeHighlight} from '@app-dev-panel/sdk/Component/CodeHighlight';
import {parseFilePath, parsePathLineAnchor} from '@app-dev-panel/sdk/Helper/filePathParser';
import {formatBytes} from '@app-dev-panel/sdk/Helper/formatBytes';
import {scrollToAnchor} from '@app-dev-panel/sdk/Helper/scrollToAnchor';
import {useEditorUrl} from '@app-dev-panel/sdk/Helper/useEditorUrl';
import {ArrowBack, FolderOpen, Lock, OpenInNew, Person, Storage} from '@mui/icons-material';
import {Box, Breadcrumbs, Button, IconButton, Link, Paper, Tooltip, Typography} from '@mui/material';
import {useEffect, useLayoutEffect, useState} from 'react';
import {useSearchParams} from 'react-router-dom';

type PathBreadcrumbsProps = {onClick: (nodeId: string) => void; path: string};

const PathBreadcrumbs = ({path, onClick}: PathBreadcrumbsProps) => {
    const paths = path.split('/').filter((s) => !!s.length);
    const fullPath: string[] = [];

    useBreadcrumbs(() => ['Inspector', 'File Explorer']);

    return (
        <Breadcrumbs>
            <Link
                underline="hover"
                color="inherit"
                href={'#'}
                onClick={(e) => {
                    onClick('/');
                    return false;
                }}
            >
                @root
            </Link>
            {paths.map((directory, index) => {
                if (index === paths.length - 1) {
                    return (
                        <Typography key={index} color="text.primary">
                            {directory}
                        </Typography>
                    );
                }
                fullPath.push(directory);

                return (
                    <Link
                        key={index}
                        underline="hover"
                        color="inherit"
                        href={'#'}
                        onClick={(e) => {
                            onClick('/' + fullPath.join('/'));
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

    const editorUrl = file ? getEditorUrl(file.path, file.startLine) : null;

    return (
        <>
            {file && (
                <Box sx={{display: 'flex', flexDirection: 'column', gap: 2}}>
                    <Box sx={{display: 'flex', alignItems: 'center', gap: 1.5}}>
                        <Button
                            variant="outlined"
                            size="small"
                            startIcon={<ArrowBack />}
                            onClick={() => {
                                setFile(null);
                                changePath(file.directory);
                            }}
                            sx={{flexShrink: 0}}
                        >
                            Back
                        </Button>
                        <Typography
                            variant="body1"
                            sx={{
                                fontWeight: 600,
                                fontFamily: "'JetBrains Mono', monospace",
                                fontSize: '14px',
                                overflow: 'hidden',
                                textOverflow: 'ellipsis',
                                whiteSpace: 'nowrap',
                                minWidth: 0,
                            }}
                        >
                            {file.path}
                        </Typography>
                        {editorUrl && (
                            <Tooltip title="Open in Editor">
                                <IconButton
                                    size="small"
                                    component="a"
                                    href={editorUrl}
                                    onClick={(e: React.MouseEvent) => e.stopPropagation()}
                                    sx={{flexShrink: 0, color: 'primary.main'}}
                                >
                                    <OpenInNew sx={{fontSize: 18}} />
                                </IconButton>
                            </Tooltip>
                        )}
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
                <>
                    <PathBreadcrumbs path={path} onClick={changePath} />
                    <TreeView tree={tree} onSelect={changePath} />
                </>
            )}

            {getClassQueryInfo.error &&
                'status' in getClassQueryInfo.error &&
                getClassQueryInfo.error.status === 404 && <Typography>File not found</Typography>}
        </>
    );
};
