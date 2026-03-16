import {NotificationSnackbar} from '@app-dev-panel/panel/Application/Component/NotificationSnackbar';
import {ErrorFallback} from '@app-dev-panel/sdk/Component/ErrorFallback';
import {AppNavSidebar} from '@app-dev-panel/sdk/Component/Layout/AppNavSidebar';
import {ScrollTopButton} from '@app-dev-panel/sdk/Component/ScrollTop';
import {componentTokens} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {Config} from '@app-dev-panel/sdk/Config';
import {Icon, IconButton, Typography} from '@mui/material';
import Box from '@mui/material/Box';
import CssBaseline from '@mui/material/CssBaseline';
import {styled} from '@mui/material/styles';
import * as React from 'react';
import {useCallback} from 'react';
import {ErrorBoundary} from 'react-error-boundary';
import {Outlet, useLocation, useNavigate} from 'react-router-dom';

// ---------------------------------------------------------------------------
// Navigation structure
// ---------------------------------------------------------------------------

const navSections = [
    {
        items: [
            {key: 'home', icon: 'home', label: 'Home', href: '/'},
            {key: 'debug', icon: 'bug_report', label: 'Debug', href: '/debug'},
        ],
    },
    {
        title: 'Config',
        items: [
            {key: 'config', icon: 'settings', label: 'Configuration', href: '/inspector/config'},
            {key: 'events', icon: 'bolt', label: 'Events', href: '/inspector/events'},
            {key: 'routes', icon: 'alt_route', label: 'Routes', href: '/inspector/routes'},
        ],
    },
    {
        title: 'Inspector',
        items: [
            {key: 'tests', icon: 'science', label: 'Tests', href: '/inspector/tests'},
            {key: 'analyse', icon: 'analytics', label: 'Analyse', href: '/inspector/analyse'},
            {key: 'files', icon: 'folder_open', label: 'File Explorer', href: '/inspector/files'},
            {key: 'translations', icon: 'translate', label: 'Translations', href: '/inspector/translations'},
            {key: 'commands', icon: 'terminal', label: 'Commands', href: '/inspector/commands'},
            {key: 'database', icon: 'storage', label: 'Database', href: '/inspector/database'},
            {key: 'cache', icon: 'cached', label: 'Cache', href: '/inspector/cache'},
            {key: 'git', icon: 'code', label: 'Git', href: '/inspector/git'},
            {key: 'phpinfo', icon: 'info', label: 'PHP Info', href: '/inspector/phpinfo'},
            {key: 'composer', icon: 'inventory_2', label: 'Composer', href: '/inspector/composer'},
            {key: 'opcache', icon: 'speed', label: 'Opcache', href: '/inspector/opcache'},
        ],
    },
    {
        title: 'Tools',
        items: [
            {key: 'gii', icon: 'build_circle', label: 'Gii', href: '/gii'},
            {key: 'open-api', icon: 'data_object', label: 'Open API', href: '/open-api'},
            {key: 'frames', icon: 'web', label: 'Frames', href: '/frames'},
        ],
    },
];

// ---------------------------------------------------------------------------
// Styled components
// ---------------------------------------------------------------------------

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
    cursor: 'pointer',
}));

const Diamond = styled('div')(({theme}) => ({
    width: 8,
    height: 8,
    backgroundColor: theme.palette.primary.main,
    transform: 'rotate(45deg)',
    borderRadius: 1,
}));

const Spacer = styled('span')({flex: 1});

const MainArea = styled(Box)({
    flex: 1,
    overflow: 'hidden',
    display: 'flex',
    justifyContent: 'center',
    padding: componentTokens.mainGap,
    gap: componentTokens.mainGap,
});

const MainInner = styled(Box)({
    display: 'flex',
    width: '100%',
    maxWidth: componentTokens.mainMaxWidth,
    gap: componentTokens.mainGap,
});

const ContentArea = styled(Box)(({theme}) => ({
    flex: 1,
    minWidth: 0,
    borderRadius: componentTokens.contentPanel.borderRadius,
    backgroundColor: theme.palette.background.paper,
    border: `1px solid ${theme.palette.divider}`,
    padding: theme.spacing(3.5, 4.5),
    overflowY: 'auto',
}));

const BuildBadge = styled(Typography)(({theme}) => ({
    fontSize: '10px',
    fontFamily: "'JetBrains Mono', monospace",
    color: theme.palette.text.disabled,
}));

// ---------------------------------------------------------------------------
// Layout component
// ---------------------------------------------------------------------------

export const Layout = React.memo(({children}: React.PropsWithChildren) => {
    const location = useLocation();
    const navigate = useNavigate();

    const handleNavigate = useCallback(
        (href: string) => {
            navigate(href);
        },
        [navigate],
    );

    const handleLogoClick = useCallback(() => {
        navigate('/');
    }, [navigate]);

    const handleRefresh = useCallback(() => {
        if ('location' in window) {
            window.location.reload();
        }
    }, []);

    return (
        <>
            <CssBaseline />
            <NotificationSnackbar />
            <Box sx={{display: 'flex', flexDirection: 'column', height: '100vh'}}>
                <BarRoot>
                    <Logo onClick={handleLogoClick}>
                        <Diamond /> ADP
                    </Logo>
                    <Spacer />
                    <BuildBadge>v{Config.buildVersion}</BuildBadge>
                    <IconButton size="small" onClick={handleRefresh}>
                        <Icon sx={{fontSize: 18}}>refresh</Icon>
                    </IconButton>
                    <IconButton size="small" href="https://github.com/app-dev-panel/app-dev-panel" target="_blank">
                        <Icon sx={{fontSize: 18}}>open_in_new</Icon>
                    </IconButton>
                </BarRoot>

                <MainArea>
                    <MainInner>
                        <AppNavSidebar
                            sections={navSections}
                            activePath={location.pathname}
                            onNavigate={handleNavigate}
                        />
                        <ContentArea>
                            <ErrorBoundary FallbackComponent={ErrorFallback} resetKeys={[location.pathname]}>
                                <Outlet />
                            </ErrorBoundary>
                        </ContentArea>
                    </MainInner>
                </MainArea>
            </Box>
            {children}
            <ScrollTopButton bottomOffset={!!children} />
        </>
    );
});
