import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {Icon, type Theme} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import React from 'react';

type RequestPillProps = {
    method: string;
    path: string;
    status: number;
    duration: string;
    onClick?: (e: React.MouseEvent<HTMLButtonElement>) => void;
};

const PillRoot = styled('button')(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1),
    padding: theme.spacing(0.625, 1.75),
    borderRadius: 20,
    border: `1px solid ${theme.palette.divider}`,
    backgroundColor: theme.palette.background.paper,
    cursor: 'pointer',
    fontSize: '13px',
    fontFamily: theme.typography.fontFamily,
    color: theme.palette.text.primary,
    width: '100%',
    '&:hover': {borderColor: theme.palette.primary.main},
}));

const statusColor = (status: number, theme: Theme): string => {
    if (status >= 500) return theme.palette.error.main;
    if (status >= 400) return theme.palette.warning.main;
    if (status >= 300) return theme.palette.warning.main;
    return theme.palette.success.main;
};

const methodColor = (method: string, theme: Theme): string => {
    switch (method.toUpperCase()) {
        case 'GET':
            return theme.palette.success.main;
        case 'POST':
            return theme.palette.primary.main;
        case 'PUT':
        case 'PATCH':
            return theme.palette.warning.main;
        case 'DELETE':
            return theme.palette.error.main;
        default:
            return theme.palette.text.secondary;
    }
};

const Separator = styled('span')(({theme}) => ({color: theme.palette.divider}));

const MethodLabel = styled('span')({fontWeight: 600, fontSize: '11px'});

const PathLabel = styled('span')({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '12px',
    flex: 1,
    overflow: 'hidden',
    textOverflow: 'ellipsis',
    whiteSpace: 'nowrap',
    textAlign: 'left',
});

const StatusLabel = styled('span')({fontWeight: 500, fontSize: '12px'});

const DurationLabel = styled('span')(({theme}) => ({color: theme.palette.text.disabled, fontSize: '12px'}));

export const RequestPill = ({method, path, status, duration, onClick}: RequestPillProps) => {
    const theme = useTheme();
    return (
        <PillRoot onClick={onClick}>
            <MethodLabel sx={{color: methodColor(method, theme)}}>{method}</MethodLabel>
            <PathLabel>{path}</PathLabel>
            <Separator>&mdash;</Separator>
            <StatusLabel sx={{color: statusColor(status, theme)}}>{status}</StatusLabel>
            <Separator>&mdash;</Separator>
            <DurationLabel>{duration}</DurationLabel>
            <Icon sx={{fontSize: 16, color: 'text.disabled'}}>expand_more</Icon>
        </PillRoot>
    );
};
