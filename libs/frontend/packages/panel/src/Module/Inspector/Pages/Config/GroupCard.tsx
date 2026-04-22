import {Box, Chip, Collapse, Icon, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import {ReactNode, useState} from 'react';

type GroupCardProps = {
    name: string;
    count: number;
    countLabel: string;
    defaultExpanded: boolean;
    preview: ReactNode;
    children: ReactNode;
};

const Card = styled(Box)(({theme}) => ({
    border: `1px solid ${theme.palette.divider}`,
    borderRadius: theme.shape.borderRadius,
    overflow: 'hidden',
    '&:not(:last-child)': {marginBottom: theme.spacing(1.5)},
}));

const Header = styled(Box, {shouldForwardProp: (p) => p !== 'expanded'})<{expanded?: boolean}>(({theme, expanded}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1.5),
    padding: theme.spacing(1.5, 2),
    cursor: 'pointer',
    backgroundColor: expanded ? theme.palette.action.hover : 'transparent',
    '&:hover': {backgroundColor: theme.palette.action.hover},
}));

const PreviewRow = styled(Box)(({theme}) => ({
    padding: theme.spacing(0, 2, 1.5, 4.5),
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '12px',
    color: theme.palette.text.secondary,
    overflow: 'hidden',
    textOverflow: 'ellipsis',
    whiteSpace: 'nowrap',
}));

export const GroupCard = ({name, count, countLabel, defaultExpanded, preview, children}: GroupCardProps) => {
    const [expanded, setExpanded] = useState(defaultExpanded);

    return (
        <Card>
            <Header expanded={expanded} onClick={() => setExpanded(!expanded)}>
                <Icon sx={{fontSize: 16, color: 'text.disabled'}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                <Typography sx={{fontWeight: 600, fontSize: '13px', flex: 1, fontFamily: 'monospace'}}>
                    {name}
                </Typography>
                <Chip
                    label={`${count} ${countLabel}`}
                    size="small"
                    sx={{fontSize: '10px', height: 20, borderRadius: 1, backgroundColor: 'action.selected'}}
                />
            </Header>

            {!expanded && <PreviewRow>{preview}</PreviewRow>}

            <Collapse in={expanded} unmountOnExit>
                {children}
            </Collapse>
        </Card>
    );
};
