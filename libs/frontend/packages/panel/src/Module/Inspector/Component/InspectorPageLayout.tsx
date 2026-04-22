import {PageHeaderProvider, usePageHeaderContext} from '@app-dev-panel/sdk/Component/PageHeader';
import {useMediaQuery, useTheme} from '@mui/material';
import {useMemo} from 'react';
import {Outlet, useLocation} from 'react-router';

// Routes where the chip header carries dynamic context (table name, service
// id, etc.) that the sidebar does NOT convey. On list pages the chip would
// only duplicate the active sidebar item, so we hide it.
const NESTED_CONTEXT_PATTERNS = [
    /^\/inspector\/storage\/database\/[^/]+/,
    /^\/inspector\/database\/[^/]+/,
    /^\/inspector\/container\/view/,
];

export const InspectorPageLayout = () => {
    const parent = usePageHeaderContext();
    const location = useLocation();
    const theme = useTheme();
    // On mobile the sidebar collapses into a drawer, so the chip is the only
    // remaining indicator of the current page — keep it visible there.
    const isCompact = useMediaQuery(theme.breakpoints.down('md'));
    const hasDynamicContext = NESTED_CONTEXT_PATTERNS.some((pattern) => pattern.test(location.pathname));

    const variant: 'chip' | 'hidden' = isCompact || hasDynamicContext ? 'chip' : 'hidden';
    const value = useMemo(() => ({...parent, variant}), [parent, variant]);

    return (
        <PageHeaderProvider value={value}>
            <Outlet />
        </PageHeaderProvider>
    );
};
