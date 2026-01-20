import {Undo} from '@mui/icons-material';
import {Box, Breadcrumbs, Button, Link, Typography} from '@mui/material';
import {CodeHighlight} from '@yiisoft/yii-dev-panel-sdk/Component/CodeHighlight';
import {parseFilePath, parsePathLineAnchor} from '@yiisoft/yii-dev-panel-sdk/Helper/filePathParser';
import {formatBytes} from '@yiisoft/yii-dev-panel-sdk/Helper/formatBytes';
import {scrollToAnchor} from '@yiisoft/yii-dev-panel-sdk/Helper/scrollToAnchor';
import {
    InspectorFile,
    InspectorFileContent,
    useLazyGetClassQuery,
    useLazyGetFilesQuery,
} from '@yiisoft/yii-dev-panel/Module/Inspector/API/Inspector';
import {TreeView} from '@yiisoft/yii-dev-panel/Module/Inspector/Component/TreeView/TreeView';
import {useEffect, useLayoutEffect, useState} from 'react';
import {useSearchParams} from 'react-router-dom';
import {useBreadcrumbs} from '@yiisoft/yii-dev-panel/Application/Context/BreadcrumbsContext';

type PathBreadcrumbsProps = {
    onClick: (nodeId: string) => void;
    path: string;
};

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

    useLayoutEffect(() => {
        if (file) {
            if (file.startLine) {
                scrollToAnchor(25, `L${file.startLine}`);
                return;
            }
            const lines = parsePathLineAnchor(window.location.hash);
            scrollToAnchor(25, lines && `L${lines[0]}`);
        }
    });
    const changePath = (path: string) => {
        setSearchParams({path});
    };

    return (
        <>
            {file && (
                <>
                    <h2>
                        <Button
                            onClick={() => {
                                setFile(null);
                                changePath(file.directory);
                            }}
                        >
                            <Undo /> Back
                        </Button>
                        {file.path}
                    </h2>
                    <CodeHighlight
                        language={file.extension}
                        code={file.content}
                        highlightLines={parsePathLineAnchor(window.location.hash)}
                    />
                    <Box>
                        <Typography>Directory: @root{file.directory}</Typography>
                        <Typography>Permissions: {file.permissions}</Typography>
                        <Typography>
                            Owner: {file.user?.name ?? file.user.uid}:{file.group?.name ?? file.group.gid}
                        </Typography>
                        <Typography>Size: {formatBytes(file.size)}</Typography>
                    </Box>
                </>
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
