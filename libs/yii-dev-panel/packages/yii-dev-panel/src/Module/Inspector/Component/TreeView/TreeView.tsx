import {SimpleTreeView, TreeItem, TreeItemProps, treeItemClasses} from '@mui/x-tree-view';
import {styled, SvgIconProps, Typography} from '@mui/material';
import Box from '@mui/material/Box';
import {formatBytes} from '@yiisoft/yii-dev-panel-sdk/Helper/formatBytes';
import {InspectorFile} from '@yiisoft/yii-dev-panel/Module/Inspector/API/Inspector';
import {fileExtensionIcon} from '@yiisoft/yii-dev-panel/Module/Inspector/Component/TreeView/iconHelper';
import * as React from 'react';
import {useMemo} from 'react';

type StyledTreeItemProps = TreeItemProps & {
    fileIcon: React.ElementType<SvgIconProps>;
    fileSize: string;
    fileName: string;
};

const StyledTreeItemRoot = styled(TreeItem)(({theme}) => ({
    color: theme.palette.text.secondary,
    [`& .${treeItemClasses.iconContainer}`]: {width: '0 !important'},
    [`& .${treeItemClasses.content}`]: {
        color: theme.palette.text.secondary,
        padding: theme.spacing(0, 1, 0, 0),
        fontWeight: theme.typography.fontWeightMedium,
        '&.Mui-expanded': {
            fontWeight: theme.typography.fontWeightRegular,
        },
        '&:hover': {
            backgroundColor: theme.palette.action.hover,
        },
        '&.Mui-focused, &.Mui-selected, &.Mui-selected.Mui-focused': {
            backgroundColor: `var(--tree-view-bg-color, ${theme.palette.action.selected})`,
            color: 'var(--tree-view-color)',
        },
        [`& .${treeItemClasses.label}`]: {
            fontWeight: 'inherit',
            color: 'inherit',
        },
    },
    // [`& .${treeItemClasses.group}`]: {
    //     marginLeft: 0,
    //     [`& .${treeItemClasses.content}`]: {
    //         paddingLeft: theme.spacing(2),
    //     },
    // },
}));

const StyledTreeItem = (props: StyledTreeItemProps) => {
    const {fileIcon, fileSize, fileName, ...other} = props;

    return (
        <StyledTreeItemRoot
            label={
                <Box sx={{display: 'flex', alignItems: 'center', py: 0.5}}>
                    <Box component={fileIcon} color="inherit" sx={{mr: 1}} />
                    <Typography variant="body2" sx={{fontWeight: 'inherit', flexGrow: 1}}>
                        {fileName}
                    </Typography>
                    <Typography variant="caption" color="inherit" sx={{whiteSpace: 'nowrap'}}>
                        {fileSize}
                    </Typography>
                </Box>
            }
            {...other}
        />
    );
};

const Tree = React.memo(({row}: {row: InspectorFile}) => {
    const fileSize = useMemo(() => formatBytes(row.size), [row]);
    return (
        <StyledTreeItem
            itemId={row.path}
            fileName={row.baseName}
            fileIcon={fileExtensionIcon(row)}
            fileSize={fileSize}
        />
    );
});

export type TreeViewProps = {
    onSelect: (nodeId: string) => void;
    tree: InspectorFile[];
};
export const TreeView = (props: TreeViewProps) => {
    const {onSelect, tree} = props;
    return (
        <SimpleTreeView
            // defaultCollapseIcon={false}
            // defaultExpandIcon={false}
            // defaultEndIcon={false}
            // defaultParentIcon={false}
            multiSelect={false}
            sx={{py: 2}}
            onItemSelectionToggle={(event, nodeId) => onSelect(nodeId)}
        >
            {tree.map((row, index) => (
                <Tree key={index} row={row} />
            ))}
        </SimpleTreeView>
    );
};
