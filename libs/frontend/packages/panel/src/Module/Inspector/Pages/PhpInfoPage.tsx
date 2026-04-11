import {useGetPhpInfoQuery} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {FullScreenCircularProgress} from '@app-dev-panel/sdk/Component/FullScreenCircularProgress';
import {QueryErrorState} from '@app-dev-panel/sdk/Component/QueryErrorState';
import {Box} from '@mui/material';
import {useEffect, useRef} from 'react';

export const PhpInfoPage = () => {
    const getPhpInfoQuery = useGetPhpInfoQuery();
    const containerRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (getPhpInfoQuery.data) {
            const shadowContainer =
                containerRef.current?.shadowRoot ?? containerRef.current?.attachShadow({mode: 'open'});

            const shadowRootElement = document.createElement('div');
            shadowRootElement.innerHTML = getPhpInfoQuery.data || '';
            shadowContainer?.appendChild(shadowRootElement);
        }
    }, [getPhpInfoQuery.data]);

    if (getPhpInfoQuery.isLoading) {
        return <FullScreenCircularProgress />;
    }

    if (getPhpInfoQuery.isError) {
        return (
            <QueryErrorState
                error={getPhpInfoQuery.error}
                title="Failed to load PHP info"
                fallback="Failed to load phpinfo() output."
                onRetry={getPhpInfoQuery.refetch}
            />
        );
    }

    return <>{getPhpInfoQuery.data && <Box ref={containerRef} sx={{overflow: 'auto'}} />}</>;
};
