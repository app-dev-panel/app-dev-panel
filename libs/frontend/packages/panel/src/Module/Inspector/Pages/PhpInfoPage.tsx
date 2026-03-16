import {useBreadcrumbs} from '@app-dev-panel/panel/Application/Context/BreadcrumbsContext';
import {useGetPhpInfoQuery} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {Box} from '@mui/material';
import {useEffect, useRef} from 'react';

export const PhpInfoPage = () => {
    const getPhpInfoQuery = useGetPhpInfoQuery();
    const containerRef = useRef<HTMLDivElement>();

    useEffect(() => {
        if (getPhpInfoQuery.data) {
            const shadowContainer =
                containerRef.current?.shadowRoot ?? containerRef.current?.attachShadow({mode: 'open'});

            const shadowRootElement = document.createElement('div');
            shadowRootElement.innerHTML = getPhpInfoQuery.data || '';
            shadowContainer?.appendChild(shadowRootElement);
        }
    }, [getPhpInfoQuery.data]);

    useBreadcrumbs(() => ['Inspector', 'PHP Info']);

    return (
        <>{!getPhpInfoQuery.isLoading && getPhpInfoQuery.data && <Box ref={containerRef} sx={{overflow: 'auto'}} />}</>
    );
};
