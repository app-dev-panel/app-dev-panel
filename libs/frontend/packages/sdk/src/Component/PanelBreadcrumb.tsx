import {styled} from '@mui/material/styles';
import {createContext, ReactNode, useContext, useRef} from 'react';

type PanelBreadcrumbValue = {
    label: ReactNode;
    consumed: {current: boolean};
};

const PanelBreadcrumbContext = createContext<PanelBreadcrumbValue | null>(null);

type ProviderProps = {
    label: ReactNode;
    children: ReactNode;
};

export const PanelBreadcrumbProvider = ({label, children}: ProviderProps) => {
    const consumed = useRef(false);
    consumed.current = false;
    return <PanelBreadcrumbContext.Provider value={{label, consumed}}>{children}</PanelBreadcrumbContext.Provider>;
};

export const usePanelBreadcrumb = (): ReactNode | null => {
    const ctx = useContext(PanelBreadcrumbContext);
    if (!ctx || ctx.consumed.current) return null;
    ctx.consumed.current = true;
    return ctx.label;
};

const Crumb = styled('span')(({theme}) => ({
    display: 'inline-flex',
    alignItems: 'center',
    gap: theme.spacing(0.5),
    color: theme.palette.text.disabled,
    opacity: 0.65,
    fontWeight: 400,
    textTransform: 'none',
    letterSpacing: 'normal',
    flexShrink: 0,
    '&::after': {
        content: '"›"',
        marginLeft: theme.spacing(0.5),
        opacity: 0.7,
    },
}));

const Section = styled('span')({opacity: 0.85});

type InlineProps = {
    label: ReactNode;
    section?: ReactNode;
};

export const PanelBreadcrumbInline = ({label, section = 'Debug'}: InlineProps) => (
    <Crumb aria-label="Section breadcrumb">
        <Section>{section}</Section>
        <span aria-hidden style={{opacity: 0.6}}>
            ›
        </span>
        <span>{label}</span>
    </Crumb>
);
