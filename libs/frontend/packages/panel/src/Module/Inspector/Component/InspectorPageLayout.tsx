import {PageHeaderProvider, usePageHeaderContext} from '@app-dev-panel/sdk/Component/PageHeader';
import {useMemo} from 'react';
import {Outlet} from 'react-router';

export const InspectorPageLayout = () => {
    const parent = usePageHeaderContext();
    const value = useMemo(() => ({...parent, variant: 'chip' as const}), [parent]);

    return (
        <PageHeaderProvider value={value}>
            <Outlet />
        </PageHeaderProvider>
    );
};
