import {NotificationSnackbar} from '@app-dev-panel/panel/Application/Component/NotificationSnackbar';
import {ErrorFallback} from '@app-dev-panel/sdk/Component/ErrorFallback';
import {ScrollTopButton} from '@app-dev-panel/sdk/Component/ScrollTop';
import {Box, CssBaseline} from '@mui/material';
import React, {PropsWithChildren} from 'react';
import {ErrorBoundary} from 'react-error-boundary';
import {Outlet} from 'react-router';

export const AppShell = React.memo(({children}: PropsWithChildren) => {
    return (
        <>
            <CssBaseline />
            <NotificationSnackbar />
            <Box sx={{display: 'flex', flexDirection: 'column', height: '100vh'}}>
                <ErrorBoundary FallbackComponent={ErrorFallback} resetKeys={[window.location.pathname]}>
                    <Outlet />
                </ErrorBoundary>
            </Box>
            {children}
            <ScrollTopButton bottomOffset={!!children} />
        </>
    );
});
