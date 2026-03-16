import {Icon, IconButton, type PaletteMode} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import {RequestPill} from '@yiisoft/yii-dev-panel-sdk/Component/Layout/RequestPill';
import {SearchTrigger} from '@yiisoft/yii-dev-panel-sdk/Component/Layout/SearchTrigger';
import {componentTokens} from '@yiisoft/yii-dev-panel-sdk/Component/Theme/tokens';
import React from 'react';

type TopBarProps = {
    method?: string;
    path?: string;
    status?: number;
    duration?: string;
    mode?: PaletteMode;
    onPrevEntry?: () => void;
    onNextEntry?: () => void;
    onEntryClick?: () => void;
    onSearchClick?: () => void;
    onThemeToggle?: () => void;
    onMoreClick?: () => void;
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

const Spacer = styled('span')({flex: 1});

export const TopBar = React.memo(
    ({
        method,
        path,
        status,
        duration,
        mode,
        onPrevEntry,
        onNextEntry,
        onEntryClick,
        onSearchClick,
        onThemeToggle,
        onMoreClick,
    }: TopBarProps) => {
        const theme = useTheme();
        const resolvedMode = mode ?? theme.palette.mode;

        return (
            <BarRoot>
                <Logo>
                    <Diamond /> ADP
                </Logo>
                {method && path && status && duration && (
                    <>
                        <RequestPill
                            method={method}
                            path={path}
                            status={status}
                            duration={duration}
                            onClick={onEntryClick}
                        />
                        <IconButton size="small" onClick={onPrevEntry}>
                            <Icon sx={{fontSize: 18}}>chevron_left</Icon>
                        </IconButton>
                        <IconButton size="small" onClick={onNextEntry}>
                            <Icon sx={{fontSize: 18}}>chevron_right</Icon>
                        </IconButton>
                    </>
                )}
                <Spacer />
                <SearchTrigger onClick={onSearchClick} />
                <IconButton size="small" onClick={onThemeToggle}>
                    <Icon sx={{fontSize: 18}}>{resolvedMode === 'dark' ? 'dark_mode' : 'light_mode'}</Icon>
                </IconButton>
                <IconButton size="small" onClick={onMoreClick}>
                    <Icon sx={{fontSize: 18}}>more_vert</Icon>
                </IconButton>
            </BarRoot>
        );
    },
);
