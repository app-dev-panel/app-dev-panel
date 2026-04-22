import {PageHeaderVariantProvider} from '@app-dev-panel/sdk/Component/PageHeader';
import {Outlet} from 'react-router';

export const InspectorPageLayout = () => (
    <PageHeaderVariantProvider value="chip">
        <Outlet />
    </PageHeaderVariantProvider>
);
