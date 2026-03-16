import {NavItem} from '@app-dev-panel/sdk/Component/Layout/NavItem';
import {componentTokens} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {Divider, Paper, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import React from 'react';

type NavSection = {title?: string; items: NavLinkItem[]};

type NavLinkItem = {key: string; icon: string; label: string; href: string; badge?: number | string};

type AppNavSidebarProps = {sections: NavSection[]; activePath?: string; onNavigate: (href: string) => void};

const SidebarRoot = styled(Paper)(({theme}) => ({
    width: componentTokens.sidebar.width,
    borderRadius: componentTokens.sidebar.borderRadius,
    display: 'flex',
    flexDirection: 'column',
    padding: theme.spacing(1.5, 1),
    gap: theme.spacing(0.25),
    flexShrink: 0,
    alignSelf: 'flex-start',
    overflowY: 'auto',
    position: 'sticky',
    top: componentTokens.topBar.height + componentTokens.mainGap,
    maxHeight: `calc(100vh - ${componentTokens.topBar.height + componentTokens.mainGap * 2}px)`,
}));

const SectionLabel = styled(Typography)(({theme}) => ({
    fontSize: '10px',
    fontWeight: 700,
    textTransform: 'uppercase',
    letterSpacing: '0.8px',
    color: theme.palette.text.disabled,
    padding: theme.spacing(0.75, 1.25, 0.25),
}));

export const AppNavSidebar = React.memo(({sections, activePath, onNavigate}: AppNavSidebarProps) => {
    return (
        <SidebarRoot variant="outlined">
            {sections.map((section, sIdx) => (
                <React.Fragment key={sIdx}>
                    {sIdx > 0 && <Divider sx={{mx: 1.25, my: 0.75}} />}
                    {section.title && <SectionLabel>{section.title}</SectionLabel>}
                    {section.items.map((item) => (
                        <NavItem
                            key={item.key}
                            icon={item.icon}
                            label={item.label}
                            badge={item.badge}
                            active={activePath === item.href}
                            onClick={() => onNavigate(item.href)}
                        />
                    ))}
                </React.Fragment>
            ))}
        </SidebarRoot>
    );
});
