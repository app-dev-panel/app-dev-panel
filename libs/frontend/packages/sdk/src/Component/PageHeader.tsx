import {Icon, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import {createContext, useContext} from 'react';

type PageHeaderVariant = 'block' | 'hidden';

export type PageHeaderContextValue = {variant: PageHeaderVariant};

const PageHeaderContext = createContext<PageHeaderContextValue>({variant: 'block'});

export const PageHeaderProvider = PageHeaderContext.Provider;
export const usePageHeaderContext = () => useContext(PageHeaderContext);

type PageHeaderProps = {title: string; icon?: string; description?: string};

const Root = styled('div')(({theme}) => ({marginBottom: theme.spacing(3)}));

const TitleRow = styled('div')(({theme}) => ({display: 'flex', alignItems: 'center', gap: theme.spacing(1.25)}));

const Title = styled(Typography)({fontWeight: 600, fontSize: '18px'});

const Description = styled(Typography)(({theme}) => ({
    fontSize: '13px',
    color: theme.palette.text.secondary,
    marginTop: theme.spacing(0.5),
}));

export const PageHeader = ({title, icon, description}: PageHeaderProps) => {
    const {variant} = usePageHeaderContext();

    if (variant === 'hidden') return null;

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
