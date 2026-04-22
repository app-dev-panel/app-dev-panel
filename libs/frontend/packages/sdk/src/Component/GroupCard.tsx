import {Box, Chip, Collapse, Icon} from '@mui/material';
import {styled} from '@mui/material/styles';
import {ReactNode, useState} from 'react';

type GroupCardProps = {
    name: ReactNode;
    count: number;
    countLabel?: string;
    defaultExpanded: boolean;
    preview?: ReactNode;
    actions?: ReactNode;
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
    '& .group-card-actions': {opacity: 0, transition: 'opacity 0.15s'},
    '&:hover .group-card-actions': {opacity: 1},
}));

const NameBox = styled(Box)({
    fontWeight: 600,
    fontSize: '13px',
    flex: 1,
    minWidth: 0,
    display: 'flex',
    alignItems: 'center',
    gap: 8,
    fontFamily: 'monospace',
});

const PreviewRow = styled(Box)(({theme}) => ({
    padding: theme.spacing(0, 2, 1.5, 4.5),
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '12px',
    color: theme.palette.text.secondary,
    overflow: 'hidden',
    textOverflow: 'ellipsis',
    whiteSpace: 'nowrap',
}));

export const GroupCard = ({name, count, countLabel, defaultExpanded, preview, actions, children}: GroupCardProps) => {
    const [expanded, setExpanded] = useState(defaultExpanded);

    return (
        <Card>
            <Header expanded={expanded} onClick={() => setExpanded(!expanded)}>
                <Icon sx={{fontSize: 16, color: 'text.disabled'}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                <NameBox>{name}</NameBox>
                {actions && (
                    <Box
                        className="group-card-actions"
                        sx={{display: 'flex', alignItems: 'center', flexShrink: 0}}
                        onClick={(e) => e.stopPropagation()}
                    >
                        {actions}
                    </Box>
                )}
                <Chip
                    label={countLabel ? `${count} ${countLabel}` : String(count)}
                    size="small"
                    sx={{
                        fontSize: '10px',
                        height: 20,
                        minWidth: 24,
                        borderRadius: 1,
                        backgroundColor: 'action.selected',
                        flexShrink: 0,
                    }}
                />
            </Header>

            {!expanded && preview && <PreviewRow>{preview}</PreviewRow>}

            <Collapse in={expanded} unmountOnExit>
                {children}
            </Collapse>
        </Card>
    );
};
