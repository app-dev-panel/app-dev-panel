import {Box, CssBaseline} from '@mui/material';
import {styled} from '@mui/material/styles';
import {ErrorFallback} from '@yiisoft/yii-dev-panel-sdk/Component/ErrorFallback';
import {ScrollTopButton} from '@yiisoft/yii-dev-panel-sdk/Component/ScrollTop';
import {componentTokens} from '@yiisoft/yii-dev-panel-sdk/Component/Theme/tokens';
import {NotificationSnackbar} from '@yiisoft/yii-dev-panel/Application/Component/NotificationSnackbar';
import React, {PropsWithChildren} from 'react';
import {ErrorBoundary} from 'react-error-boundary';
import {Outlet} from 'react-router';

const MainArea = styled(Box)(({theme}) => ({
    flex: 1,
    overflow: 'hidden',
    display: 'flex',
    justifyContent: 'center',
    padding: componentTokens.mainGap,
    gap: componentTokens.mainGap,
}));

const MainInner = styled(Box)({
    display: 'flex',
    width: '100%',
    maxWidth: componentTokens.mainMaxWidth,
    gap: componentTokens.mainGap,
});

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
