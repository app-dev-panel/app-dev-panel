import {RequestPill} from '@app-dev-panel/sdk/Component/Layout/RequestPill';
import {SearchTrigger} from '@app-dev-panel/sdk/Component/Layout/SearchTrigger';
import {componentTokens} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {Icon, IconButton, type PaletteMode} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import React from 'react';

type TopBarProps = {
    method?: string;
    path?: string;
    status?: number;
    duration?: string;
    mode?: PaletteMode;
    autoRefresh?: boolean;
    onPrevEntry?: () => void;
    onNextEntry?: () => void;
    onEntryClick?: (e: React.MouseEvent) => void;
    onSearchClick?: () => void;
    onThemeToggle?: () => void;
    onAutoRefreshToggle?: () => void;
};

const BarRoot = styled('header')(({theme}) => ({
    height: componentTokens.topBar.height,
    backgroundColor: theme.palette.background.paper,
    borderBottom: `1px solid ${theme.palette.divider}`,
    display: 'flex',
    alignItems: 'center',
    padding: theme.spacing(0, 2.5),
    gap: theme.spacing(2),
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
}));

const Diamond = styled('div')(({theme}) => ({
    width: 8,
    height: 8,
    backgroundColor: theme.palette.primary.main,
    transform: 'rotate(45deg)',
    borderRadius: 1,
}));

export const TopBar = React.memo(
    ({
        method,
        path,
        status,
        duration,
        mode,
        autoRefresh,
        onPrevEntry,
        onNextEntry,
        onEntryClick,
        onSearchClick,
        onThemeToggle,
        onAutoRefreshToggle,
    }: TopBarProps) => {
        const theme = useTheme();
        const resolvedMode = mode ?? theme.palette.mode;

        return (
            <BarRoot>
                <Logo>
                    <Diamond /> ADP
                </Logo>
                <IconButton size="small" onClick={onPrevEntry} disabled={!method}>
                    <Icon sx={{fontSize: 18}}>chevron_left</Icon>
                </IconButton>
                <IconButton size="small" onClick={onNextEntry} disabled={!method}>
                    <Icon sx={{fontSize: 18}}>chevron_right</Icon>
                </IconButton>
                <div style={{flex: 1, display: 'flex', justifyContent: 'center'}}>
                    {method && path && status != null && duration && (
                        <div style={{maxWidth: 500, width: '100%'}}>
                            <RequestPill
                                method={method}
                                path={path}
                                status={status}
                                duration={duration}
                                onClick={onEntryClick}
                            />
                        </div>
                    )}
                </div>
                <SearchTrigger onClick={onSearchClick} />
                <IconButton
                    size="small"
                    onClick={onAutoRefreshToggle}
                    title={autoRefresh ? 'Auto-refresh on' : 'Auto-refresh off'}
                >
                    <Icon sx={{fontSize: 18, color: autoRefresh ? 'success.main' : undefined}}>
                        {autoRefresh ? 'sync' : 'sync_disabled'}
                    </Icon>
                </IconButton>
                <IconButton size="small" onClick={onThemeToggle}>
                    <Icon sx={{fontSize: 18}}>{resolvedMode === 'dark' ? 'dark_mode' : 'light_mode'}</Icon>
                </IconButton>
            </BarRoot>
        );
    },
);
