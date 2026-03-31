import {RequestPill} from '@app-dev-panel/sdk/Component/Layout/RequestPill';
import {SearchTrigger} from '@app-dev-panel/sdk/Component/Layout/SearchTrigger';
import {DuckIcon} from '@app-dev-panel/sdk/Component/SvgIcon/DuckIcon';
import {componentTokens} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {editorPresetLabels, type EditorPreset} from '@app-dev-panel/sdk/Helper/editorUrl';
import {
    Autocomplete,
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
    TextField,
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
    isRefreshing?: boolean;
    showInactiveCollectors?: boolean;
    mcpEnabled?: boolean;
    notificationCount?: number;
    editorPreset?: EditorPreset;
    editorCustomTemplate?: string;
    onPrevEntry?: () => void;
    onNextEntry?: () => void;
    onEntryClick?: (e: React.MouseEvent) => void;
    onSearchClick?: () => void;
    onThemeToggle?: () => void;
    onAutoRefreshToggle?: () => void;
    onRefresh?: () => void;
    onShowInactiveCollectorsChange?: (value: boolean) => void;
    onMcpEnabledChange?: (value: boolean) => void;
    onEditorPresetChange?: (preset: EditorPreset) => void;
    onEditorCustomTemplateChange?: (template: string) => void;
    onNotificationsClick?: (e: React.MouseEvent<HTMLElement>) => void;
    onLogoClick?: () => void;
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
    cursor: 'pointer',
    userSelect: 'none',
    '&:hover': {opacity: 0.8},
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
        isRefreshing,
        showInactiveCollectors,
        mcpEnabled,
        onPrevEntry,
        onNextEntry,
        onEntryClick,
        onSearchClick,
        onThemeToggle,
        onAutoRefreshToggle,
        onRefresh,
        onShowInactiveCollectorsChange,
        onMcpEnabledChange,
        editorPreset,
        editorCustomTemplate,
        onEditorPresetChange,
        onEditorCustomTemplateChange,
        notificationCount,
        onNotificationsClick,
        onLogoClick,
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
                <Logo onClick={onLogoClick}>
                    <DuckIcon sx={{fontSize: 22}} /> App Dev Panel
                </Logo>
                <CenterGroup>
                    <IconButton size="small" onClick={onPrevEntry} disabled={!onPrevEntry} aria-label="Previous entry">
                        <Icon sx={{fontSize: 18}}>chevron_left</Icon>
                    </IconButton>
                    <IconButton size="small" onClick={onNextEntry} disabled={!onNextEntry} aria-label="Next entry">
                        <Icon sx={{fontSize: 18}}>chevron_right</Icon>
                    </IconButton>
                    <PillContainer>
                        {method && path != null && status != null ? (
                            <RequestPill
                                method={method}
                                path={path}
                                status={status}
                                duration={duration ?? ''}
                                onClick={onEntryClick}
                            />
                        ) : (
                            <div style={{height: 32}} />
                        )}
                    </PillContainer>
                    <IconButton size="small" onClick={onRefresh} disabled={isRefreshing} title="Refresh entries">
                        <Icon sx={{fontSize: 18}}>{isRefreshing ? 'hourglass_empty' : 'refresh'}</Icon>
                    </IconButton>
                    <IconButton
                        size="small"
                        onClick={onAutoRefreshToggle}
                        title={
                            autoRefresh ? 'Auto-latest: on (switch to newest entry automatically)' : 'Auto-latest: off'
                        }
                    >
                        <Icon sx={{fontSize: 18, color: autoRefresh ? 'success.main' : undefined}}>
                            {autoRefresh ? 'sync' : 'sync_disabled'}
                        </Icon>
                    </IconButton>
                </CenterGroup>
                <SearchTrigger onClick={onSearchClick} />
                <IconButton size="small" onClick={onThemeToggle} aria-label="Toggle theme">
                    <Icon sx={{fontSize: 18}}>{resolvedMode === 'dark' ? 'dark_mode' : 'light_mode'}</Icon>
                </IconButton>
                <IconButton size="small" onClick={onNotificationsClick} aria-label="Notifications">
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
                <IconButton size="small" onClick={handleMenuOpen} aria-label="More options">
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
                        <Box sx={{display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', py: 1}}>
                            <Box>
                                <Typography variant="body1">MCP Server</Typography>
                                <Typography variant="body2" color="text.secondary">
                                    Enable MCP endpoint for AI assistant integration
                                </Typography>
                            </Box>
                            <Switch
                                checked={mcpEnabled ?? true}
                                onChange={(_, checked) => onMcpEnabledChange?.(checked)}
                                sx={{ml: 2, flexShrink: 0}}
                            />
                        </Box>
                        <Box sx={{borderTop: 1, borderColor: 'divider', pt: 2, mt: 1}}>
                            <Typography variant="body1" sx={{mb: 1.5}}>
                                Editor Integration
                            </Typography>
                            <Autocomplete
                                size="small"
                                options={Object.keys(editorPresetLabels) as EditorPreset[]}
                                getOptionLabel={(option) => editorPresetLabels[option] ?? option}
                                value={editorPreset ?? 'none'}
                                onChange={(_, value) => onEditorPresetChange?.(value ?? 'none')}
                                disableClearable
                                renderInput={(params) => <TextField {...params} label="Editor" />}
                                sx={{mb: 1.5}}
                            />
                            {editorPreset === 'custom' && (
                                <TextField
                                    fullWidth
                                    size="small"
                                    label="Custom URL template"
                                    placeholder="myeditor://open?file={file}&line={line}"
                                    value={editorCustomTemplate ?? ''}
                                    onChange={(e) => onEditorCustomTemplateChange?.(e.target.value)}
                                    helperText="Use {file} and {line} placeholders"
                                />
                            )}
                        </Box>
                    </DialogContent>
                </Dialog>
            </BarRoot>
        );
    },
);
