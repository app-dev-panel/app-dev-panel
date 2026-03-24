import {NavItem} from '@app-dev-panel/sdk/Component/Layout/NavItem';
import {componentTokens} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {Collapse, Divider, Icon, Paper, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import React, {useCallback, useEffect, useRef, useState} from 'react';

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

type NavChild = {key: string; icon: string; label: string; badge?: number | string; badgeVariant?: 'default' | 'error'};

type NavSection = {key: string; icon: string; label: string; href: string; children?: NavChild[]};

type UnifiedSidebarProps = {
    sections: NavSection[];
    activePath: string;
    activeChildKey?: string;
    onNavigate: (href: string) => void;
    onChildClick?: (sectionKey: string, childKey: string) => void;
};

// ---------------------------------------------------------------------------
// Styled components
// ---------------------------------------------------------------------------

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
}));

const ChildList = styled('div')(({theme}) => ({
    paddingLeft: theme.spacing(2),
    display: 'flex',
    flexDirection: 'column',
    gap: theme.spacing(0.25),
}));

const ExpandButton = styled('span')(({theme}) => ({
    marginLeft: 'auto',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    width: 24,
    height: 24,
    borderRadius: theme.shape.borderRadius,
    flexShrink: 0,
    cursor: 'pointer',
    color: theme.palette.text.disabled,
    transition: 'background-color 0.12s ease, color 0.12s ease',
    '&:hover': {backgroundColor: theme.palette.action.hover, color: theme.palette.text.primary},
}));

const ExpandIcon = styled(Icon)({fontSize: 16, transition: 'transform 0.15s ease'});

const SectionHeader = styled('div', {shouldForwardProp: (p) => p !== 'active'})<{active: boolean}>(
    ({theme, active}) => ({
        height: componentTokens.navItem.height,
        borderRadius: componentTokens.navItem.borderRadius,
        display: 'flex',
        alignItems: 'center',
        padding: theme.spacing(0, 1.25),
        gap: theme.spacing(1.25),
        cursor: 'pointer',
        color: theme.palette.text.secondary,
        transition: 'all 0.12s ease',
        whiteSpace: 'nowrap',
        flexShrink: 0,
        position: 'relative',
        userSelect: 'none',
        '&:hover': {backgroundColor: theme.palette.action.hover, color: theme.palette.text.primary},
        ...(active && {
            backgroundColor: theme.palette.primary.light,
            color: theme.palette.primary.main,
            fontWeight: 500,
            '&::before': {
                content: '""',
                position: 'absolute',
                left: 0,
                top: 8,
                bottom: 8,
                width: componentTokens.navItem.activeBarWidth,
                backgroundColor: theme.palette.primary.main,
                borderRadius: '0 2px 2px 0',
            },
        }),
    }),
);

const SectionLabel = styled(Typography)(({theme}) => ({fontSize: theme.typography.body2.fontSize}));

// ---------------------------------------------------------------------------
// Component
// ---------------------------------------------------------------------------

export const UnifiedSidebar = React.memo(
    ({sections, activePath, activeChildKey, onNavigate, onChildClick}: UnifiedSidebarProps) => {
        const [collapsed, setCollapsed] = useState<Record<string, boolean>>({});
        const prevPathRef = useRef(activePath);

        // Auto-expand when navigating to a new section
        useEffect(() => {
            if (prevPathRef.current !== activePath) {
                prevPathRef.current = activePath;
                // Find the section that matches the new path and uncollapse it
                for (const section of sections) {
                    const isActive = section.href === '/' ? activePath === '/' : activePath.startsWith(section.href);
                    if (
                        isActive &&
                        section.children &&
                        section.children.length > 0 &&
                        collapsed[section.key] === true
                    ) {
                        setCollapsed((prev) => {
                            const next = {...prev};
                            delete next[section.key];
                            return next;
                        });
                    }
                }
            }
        }, [activePath, sections, collapsed]);

        const handleToggleExpand = useCallback(
            (sectionKey: string) => {
                setCollapsed((prev) => {
                    const section = sections.find((s) => s.key === sectionKey);
                    const isActive = section
                        ? section.href === '/'
                            ? activePath === '/'
                            : activePath.startsWith(section.href)
                        : false;
                    const current = prev[sectionKey];
                    // Determine current visual state
                    const currentlyExpanded = current === false || (current === undefined && isActive);
                    return {...prev, [sectionKey]: currentlyExpanded};
                });
            },
            [sections, activePath],
        );

        return (
            <SidebarRoot variant="outlined">
                {sections.map((section, idx) => {
                    const isSectionMatch =
                        section.href === '/' ? activePath === '/' : activePath.startsWith(section.href);
                    const hasChildren = section.children && section.children.length > 0;
                    const hasActiveChild =
                        hasChildren &&
                        activeChildKey != null &&
                        section.children!.some((c) => c.key === activeChildKey);
                    const isActiveSection = isSectionMatch && !hasActiveChild;
                    const manualState = collapsed[section.key];
                    const isExpanded =
                        hasChildren && (manualState === false || (manualState === undefined && isSectionMatch));

                    return (
                        <React.Fragment key={section.key}>
                            {idx > 0 && idx === sections.length - 3 && <Divider sx={{mx: 1.25, my: 0.75}} />}
                            {hasChildren ? (
                                <>
                                    <SectionHeader active={isActiveSection} onClick={() => onNavigate(section.href)}>
                                        <Icon sx={{fontSize: 19, flexShrink: 0}}>{section.icon}</Icon>
                                        <SectionLabel>{section.label}</SectionLabel>
                                        <ExpandButton
                                            onClick={(e) => {
                                                e.stopPropagation();
                                                handleToggleExpand(section.key);
                                            }}
                                        >
                                            <ExpandIcon
                                                sx={{transform: isExpanded ? 'rotate(180deg)' : 'rotate(0deg)'}}
                                            >
                                                expand_more
                                            </ExpandIcon>
                                        </ExpandButton>
                                    </SectionHeader>
                                    <Collapse in={isExpanded} timeout={150}>
                                        <ChildList>
                                            {section.children!.map((child) => (
                                                <NavItem
                                                    key={child.key}
                                                    icon={child.icon}
                                                    label={child.label}
                                                    badge={child.badge}
                                                    badgeVariant={child.badgeVariant}
                                                    active={activeChildKey === child.key}
                                                    onClick={() =>
                                                        onChildClick
                                                            ? onChildClick(section.key, child.key)
                                                            : onNavigate(section.href)
                                                    }
                                                />
                                            ))}
                                        </ChildList>
                                    </Collapse>
                                </>
                            ) : (
                                <NavItem
                                    icon={section.icon}
                                    label={section.label}
                                    active={isActiveSection}
                                    onClick={() => onNavigate(section.href)}
                                />
                            )}
                        </React.Fragment>
                    );
                })}
            </SidebarRoot>
        );
    },
);
