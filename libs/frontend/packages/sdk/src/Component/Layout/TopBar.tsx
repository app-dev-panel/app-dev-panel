import {RequestPill} from '@app-dev-panel/sdk/Component/Layout/RequestPill';
import {SearchTrigger} from '@app-dev-panel/sdk/Component/Layout/SearchTrigger';
import {componentTokens} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {
    Badge,
    Box,
    Dialog,
    DialogContent,
    DialogTitle,
    Icon,
    IconButton,
    ListItemIcon,
    ListItemText,
    Menu,
    MenuItem,
    Switch,
    Typography,
    type PaletteMode,
} from '@mui/material';
import {keyframes, styled, useTheme} from '@mui/material/styles';
import React, {useCallback, useEffect, useRef, useState} from 'react';

const bellShake = keyframes`
    0% { transform: rotate(0deg); }
    15% { transform: rotate(14deg); }
    30% { transform: rotate(-14deg); }
    45% { transform: rotate(10deg); }
    60% { transform: rotate(-8deg); }
    75% { transform: rotate(4deg); }
    90% { transform: rotate(-2deg); }
    100% { transform: rotate(0deg); }
`;

const badgePulse = keyframes`
    0% { transform: scale(1) translate(50%, -50%); }
    40% { transform: scale(1.4) translate(50%, -50%); }
    100% { transform: scale(1) translate(50%, -50%); }
`;

type TopBarProps = {
    method?: string;
    path?: string;
    status?: number;
    duration?: string;
    mode?: PaletteMode;
    autoRefresh?: boolean;
    showInactiveCollectors?: boolean;
    notificationCount?: number;
    onPrevEntry?: () => void;
    onNextEntry?: () => void;
    onEntryClick?: (e: React.MouseEvent) => void;
    onSearchClick?: () => void;
    onThemeToggle?: () => void;
    onAutoRefreshToggle?: () => void;
    onShowInactiveCollectorsChange?: (value: boolean) => void;
    onNotificationsClick?: (e: React.MouseEvent<HTMLElement>) => void;
};

const BarRoot = styled('header')(({theme}) => ({
    height: componentTokens.topBar.height,
    backgroundColor: theme.palette.background.paper,
    borderBottom: `1px solid ${theme.palette.divider}`,
    display: 'flex',
    alignItems: 'center',
    padding: theme.spacing(0, 2.5),
    gap: theme.spacing(1),
    flexShrink: 0,
    position: 'sticky',
    top: 0,
    zIndex: theme.zIndex.appBar,
}));

const Logo = styled('div')(({theme}) => ({
    fontWeight: 700,
    fontSize: 15,
    color: theme.palette.primary.main,
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(0.75),
    flexShrink: 0,
}));

const Diamond = styled('div')(({theme}) => ({
    width: 8,
    height: 8,
    backgroundColor: theme.palette.primary.main,
    transform: 'rotate(45deg)',
    borderRadius: 1,
}));

const CenterGroup = styled('div')({
    flex: 1,
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 4,
    minWidth: 0,
});

const PillContainer = styled('div')({maxWidth: 700, width: '100%', minWidth: 0});

export const TopBar = React.memo(
    ({
        method,
        path,
        status,
        duration,
        mode,
        autoRefresh,
        showInactiveCollectors,
        onPrevEntry,
        onNextEntry,
        onEntryClick,
        onSearchClick,
        onThemeToggle,
        onAutoRefreshToggle,
        onShowInactiveCollectorsChange,
        notificationCount,
        onNotificationsClick,
    }: TopBarProps) => {
        const theme = useTheme();
        const resolvedMode = mode ?? theme.palette.mode;

        // More menu state
        const [menuAnchor, setMenuAnchor] = useState<HTMLElement | null>(null);
        const menuOpen = Boolean(menuAnchor);
        const handleMenuOpen = useCallback((e: React.MouseEvent<HTMLElement>) => setMenuAnchor(e.currentTarget), []);
        const handleMenuClose = useCallback(() => setMenuAnchor(null), []);

        // Settings dialog state
        const [settingsOpen, setSettingsOpen] = useState(false);
        const handleSettingsOpen = useCallback(() => {
            setMenuAnchor(null);
            setSettingsOpen(true);
        }, []);
        const handleSettingsClose = useCallback(() => setSettingsOpen(false), []);

        // Bell animation on count change
        const [bellAnimating, setBellAnimating] = useState(false);
        const prevCountRef = useRef(notificationCount ?? 0);
        useEffect(() => {
            const prev = prevCountRef.current;
            const current = notificationCount ?? 0;
            prevCountRef.current = current;
            if (current > prev && current > 0) {
                setBellAnimating(true);
                const timer = setTimeout(() => setBellAnimating(false), 600);
                return () => clearTimeout(timer);
            }
        }, [notificationCount]);

        return (
            <BarRoot>
                <Logo>
                    <Diamond /> App Dev Panel
                </Logo>
                <CenterGroup>
                    <IconButton size="small" onClick={onPrevEntry} disabled={!method}>
                        <Icon sx={{fontSize: 18}}>chevron_left</Icon>
                    </IconButton>
                    <IconButton size="small" onClick={onNextEntry} disabled={!method}>
                        <Icon sx={{fontSize: 18}}>chevron_right</Icon>
                    </IconButton>
                    <PillContainer>
                        {method && path && status != null && duration ? (
                            <RequestPill
                                method={method}
                                path={path}
                                status={status}
                                duration={duration}
                                onClick={onEntryClick}
                            />
                        ) : (
                            <div style={{height: 32}} />
                        )}
                    </PillContainer>
                    <IconButton
                        size="small"
                        onClick={onAutoRefreshToggle}
                        title={autoRefresh ? 'Auto-refresh on' : 'Auto-refresh off'}
                    >
                        <Icon sx={{fontSize: 18, color: autoRefresh ? 'success.main' : undefined}}>
                            {autoRefresh ? 'sync' : 'sync_disabled'}
                        </Icon>
                    </IconButton>
                </CenterGroup>
                <SearchTrigger onClick={onSearchClick} />
                <IconButton size="small" onClick={onThemeToggle}>
                    <Icon sx={{fontSize: 18}}>{resolvedMode === 'dark' ? 'dark_mode' : 'light_mode'}</Icon>
                </IconButton>
                <IconButton size="small" onClick={onNotificationsClick}>
                    <Badge
                        badgeContent={notificationCount}
                        color="error"
                        max={99}
                        sx={{
                            '& .MuiBadge-badge': {
                                fontSize: 10,
                                height: 16,
                                minWidth: 16,
                                animation: bellAnimating ? `${badgePulse} 0.4s ease-out` : 'none',
                            },
                        }}
                    >
                        <Icon
                            sx={{
                                fontSize: 18,
                                animation: bellAnimating ? `${bellShake} 0.5s ease-in-out` : 'none',
                                transformOrigin: 'top center',
                            }}
                        >
                            notifications
                        </Icon>
                    </Badge>
                </IconButton>
                <IconButton size="small" onClick={handleMenuOpen}>
                    <Icon sx={{fontSize: 18}}>more_vert</Icon>
                </IconButton>

                <Menu
                    anchorEl={menuAnchor}
                    open={menuOpen}
                    onClose={handleMenuClose}
                    anchorOrigin={{vertical: 'bottom', horizontal: 'right'}}
                    transformOrigin={{vertical: 'top', horizontal: 'right'}}
                    slotProps={{paper: {sx: {minWidth: 160}}}}
                >
                    <MenuItem onClick={handleSettingsOpen}>
                        <ListItemIcon>
                            <Icon sx={{fontSize: 20}}>settings</Icon>
                        </ListItemIcon>
                        <ListItemText>Settings</ListItemText>
                    </MenuItem>
                </Menu>

                <Dialog open={settingsOpen} onClose={handleSettingsClose} maxWidth="xs" fullWidth>
                    <DialogTitle sx={{display: 'flex', alignItems: 'center', justifyContent: 'space-between', pb: 1}}>
                        Settings
                        <IconButton size="small" onClick={handleSettingsClose} edge="end">
                            <Icon sx={{fontSize: 20}}>close</Icon>
                        </IconButton>
                    </DialogTitle>
                    <DialogContent>
                        <Box sx={{display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', py: 1}}>
                            <Box>
                                <Typography variant="body1">Show inactive collectors</Typography>
                                <Typography variant="body2" color="text.secondary">
                                    Display collectors with no collected data in the sidebar
                                </Typography>
                            </Box>
                            <Switch
                                checked={showInactiveCollectors ?? false}
                                onChange={(_, checked) => onShowInactiveCollectorsChange?.(checked)}
                                sx={{ml: 2, flexShrink: 0}}
                            />
                        </Box>
                    </DialogContent>
                </Dialog>
            </BarRoot>
        );
    },
);
