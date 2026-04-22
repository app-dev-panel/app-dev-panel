import {PageHeaderVariantProvider} from '@app-dev-panel/sdk/Component/PageHeader';
import {styled} from '@mui/material/styles';
import {Outlet} from 'react-router';

const Frame = styled('div')(({theme}) => ({
    position: 'relative',
    marginTop: theme.spacing(1.5),
    padding: theme.spacing(3.5, 2.5, 2.5),
    border: `1px solid ${theme.palette.divider}`,
    borderRadius: theme.shape.borderRadius * 1.5,
    backgroundColor: theme.palette.background.paper,
    [theme.breakpoints.up('sm')]: {padding: theme.spacing(4, 3, 3)},
}));

export const InspectorPageLayout = () => {
    return (
        <PageHeaderVariantProvider value="chip">
            <Frame>
                <Outlet />
            </Frame>
        </PageHeaderVariantProvider>
    );
};
