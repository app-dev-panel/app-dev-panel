import {Icon, Tooltip, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import {createContext, useContext} from 'react';

type PageHeaderVariant = 'block' | 'chip';

const PageHeaderVariantContext = createContext<PageHeaderVariant>('block');

export const PageHeaderVariantProvider = PageHeaderVariantContext.Provider;

type PageHeaderProps = {title: string; icon?: string; description?: string};

const Root = styled('div')(({theme}) => ({marginBottom: theme.spacing(3)}));

const TitleRow = styled('div')(({theme}) => ({display: 'flex', alignItems: 'center', gap: theme.spacing(1.25)}));

const Title = styled(Typography)({fontWeight: 600, fontSize: '18px'});

const Description = styled(Typography)(({theme}) => ({
    fontSize: '13px',
    color: theme.palette.text.secondary,
    marginTop: theme.spacing(0.5),
}));

const Chip = styled('div')(({theme}) => ({
    position: 'absolute',
    top: 0,
    left: theme.spacing(2.5),
    transform: 'translateY(-50%)',
    display: 'inline-flex',
    alignItems: 'center',
    gap: theme.spacing(0.75),
    padding: theme.spacing(0.5, 1.25),
    borderRadius: 999,
    border: `1px solid ${theme.palette.divider}`,
    backgroundColor: theme.palette.background.paper,
    color: theme.palette.text.primary,
    fontWeight: 600,
    fontSize: '13px',
    lineHeight: 1.2,
    whiteSpace: 'nowrap',
    zIndex: 1,
}));

export const PageHeader = ({title, icon, description}: PageHeaderProps) => {
    const variant = useContext(PageHeaderVariantContext);

    if (variant === 'chip') {
        const chip = (
            <Chip>
                {icon && <Icon sx={{fontSize: 16, color: 'primary.main'}}>{icon}</Icon>}
                <span>{title}</span>
            </Chip>
        );

        return description ? (
            <Tooltip title={description} placement="bottom-start" arrow>
                {chip}
            </Tooltip>
        ) : (
            chip
        );
    }

    return (
        <Root>
            <TitleRow>
                {icon && <Icon sx={{fontSize: 22, color: 'text.secondary'}}>{icon}</Icon>}
                <Title>{title}</Title>
            </TitleRow>
            {description && <Description>{description}</Description>}
        </Root>
    );
};
