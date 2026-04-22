import {Icon, Tooltip, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import {createContext, useContext, useEffect} from 'react';
import {createPortal} from 'react-dom';

type PageHeaderVariant = 'block' | 'chip';

export type PageHeaderContextValue = {variant: PageHeaderVariant; slot: HTMLElement | null};

const PageHeaderContext = createContext<PageHeaderContextValue>({variant: 'block', slot: null});

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

const ChipBody = styled('span')(({theme}) => ({
    display: 'inline-flex',
    alignItems: 'center',
    gap: theme.spacing(1),
    padding: theme.spacing(0.875, 1.75),
    borderRadius: 999,
    border: `1px solid ${theme.palette.divider}`,
    backgroundColor: theme.palette.background.paper,
    color: theme.palette.text.primary,
    fontWeight: 600,
    fontSize: '14px',
    lineHeight: 1.2,
    whiteSpace: 'nowrap',
    pointerEvents: 'auto',
}));

const ChipInfoIcon = styled(Icon)(({theme}) => ({fontSize: 16, color: theme.palette.text.secondary, cursor: 'help'}));

// Tracks the number of chip PageHeaders currently mounted in the single shared
// slot. Only used for a dev-mode guard against duplicate headers on one page.
let chipOccupants = 0;

export const PageHeader = ({title, icon, description}: PageHeaderProps) => {
    const {variant, slot} = usePageHeaderContext();

    useEffect(() => {
        if (!import.meta.env.DEV || variant !== 'chip' || !slot) return;
        chipOccupants += 1;
        if (chipOccupants > 1) {
            console.warn(
                '[PageHeader] Multiple chip PageHeaders are mounted at once — only one should be active per page.',
            );
        }
        return () => {
            chipOccupants -= 1;
        };
    }, [variant, slot]);

    if (variant === 'chip' && slot) {
        return createPortal(
            <ChipBody>
                {icon && <Icon sx={{fontSize: 18, color: 'primary.main'}}>{icon}</Icon>}
                <span>{title}</span>
                {description && (
                    <Tooltip title={description} placement="bottom-start" arrow>
                        <ChipInfoIcon>info_outline</ChipInfoIcon>
                    </Tooltip>
                )}
            </ChipBody>,
            slot,
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
