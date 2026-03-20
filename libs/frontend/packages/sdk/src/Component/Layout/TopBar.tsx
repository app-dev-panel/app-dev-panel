import {RequestPill} from '@app-dev-panel/sdk/Component/Layout/RequestPill';
import {SearchTrigger} from '@app-dev-panel/sdk/Component/Layout/SearchTrigger';
import {componentTokens} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {
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
import {styled, useTheme} from '@mui/material/styles';
import React, {useCallback, useState} from 'react';

type TopBarProps = {
    method?: string;
    path?: string;
    status?: number;
    duration?: string;
    mode?: PaletteMode;
    autoRefresh?: boolean;
    showInactiveCollectors?: boolean;
    onPrevEntry?: () => void;
    onNextEntry?: () => void;
    onEntryClick?: (e: React.MouseEvent) => void;
    onSearchClick?: () => void;
    onThemeToggle?: () => void;
    onAutoRefreshToggle?: () => void;
    onShowInactiveCollectorsChange?: (value: boolean) => void;
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
