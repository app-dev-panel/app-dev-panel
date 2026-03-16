import {Icon} from '@mui/material';
import {styled} from '@mui/material/styles';
import {primitives} from '@yiisoft/yii-dev-panel-sdk/Component/Theme/tokens';

type RequestPillProps = {method: string; path: string; status: number; duration: string; onClick?: () => void};

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

export const RequestPill = ({method, path, status, duration, onClick}: RequestPillProps) => {
    return (
        <PillRoot onClick={onClick}>
            <span style={{fontWeight: 600, color: methodColor(method), fontSize: '11px'}}>{method}</span>
            <span style={{fontFamily: primitives.fontFamilyMono, fontSize: '12px'}}>{path}</span>
            <Separator>&mdash;</Separator>
            <span style={{color: statusColor(status), fontWeight: 500, fontSize: '12px'}}>{status}</span>
            <Separator>&mdash;</Separator>
            <span style={{color: primitives.gray400, fontSize: '12px'}}>{duration}</span>
            <Icon sx={{fontSize: 16, color: 'text.disabled'}}>expand_more</Icon>
        </PillRoot>
    );
};
