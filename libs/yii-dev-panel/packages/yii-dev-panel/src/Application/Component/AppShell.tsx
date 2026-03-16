import {Box, CssBaseline} from '@mui/material';
import {ErrorFallback} from '@yiisoft/yii-dev-panel-sdk/Component/ErrorFallback';
import {ScrollTopButton} from '@yiisoft/yii-dev-panel-sdk/Component/ScrollTop';
import {NotificationSnackbar} from '@yiisoft/yii-dev-panel/Application/Component/NotificationSnackbar';
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
