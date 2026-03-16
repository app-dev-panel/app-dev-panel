import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {Icon} from '@mui/material';
import {styled} from '@mui/material/styles';
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
    '&:hover': {borderColor: theme.palette.primary.main},
}));

const statusColor = (status: number): string => {
    if (status >= 500) return primitives.red600;
    if (status >= 400) return primitives.amber600;
    if (status >= 300) return primitives.amber600;
    return primitives.green600;
};

const methodColor = (method: string): string => {
    switch (method.toUpperCase()) {
        case 'GET':
            return primitives.green600;
        case 'POST':
            return primitives.blue500;
        case 'PUT':
        case 'PATCH':
            return primitives.amber600;
        case 'DELETE':
            return primitives.red600;
        default:
            return primitives.gray600;
    }
};

const Separator = styled('span')(({theme}) => ({color: theme.palette.divider}));

const MethodLabel = styled('span')({fontWeight: 600, fontSize: '11px'});

const PathLabel = styled('span')({fontFamily: primitives.fontFamilyMono, fontSize: '12px'});

const StatusLabel = styled('span')({fontWeight: 500, fontSize: '12px'});

const DurationLabel = styled('span')({color: primitives.gray400, fontSize: '12px'});

export const RequestPill = ({method, path, status, duration, onClick}: RequestPillProps) => {
    return (
        <PillRoot onClick={onClick}>
            <MethodLabel sx={{color: methodColor(method)}}>{method}</MethodLabel>
            <PathLabel>{path}</PathLabel>
            <Separator>&mdash;</Separator>
            <StatusLabel sx={{color: statusColor(status)}}>{status}</StatusLabel>
            <Separator>&mdash;</Separator>
            <DurationLabel>{duration}</DurationLabel>
            <Icon sx={{fontSize: 16, color: 'text.disabled'}}>expand_more</Icon>
        </PillRoot>
    );
};
