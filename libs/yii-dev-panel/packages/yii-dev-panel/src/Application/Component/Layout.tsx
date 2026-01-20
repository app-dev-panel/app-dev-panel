import {ContentCut, GitHub, Refresh} from '@mui/icons-material';
import {
    Breadcrumbs,
    CssBaseline,
    Divider,
    Link,
    List,
    ListItem,
    ListItemButton,
    ListItemIcon,
    ListItemSecondaryAction,
    ListItemText,
} from '@mui/material';
import Box from '@mui/material/Box';
import Container from '@mui/material/Container';
import Typography from '@mui/material/Typography';
import {ErrorFallback} from '@yiisoft/yii-dev-panel-sdk/Component/ErrorFallback';
import {ScrollTopButton} from '@yiisoft/yii-dev-panel-sdk/Component/ScrollTop';
import {YiiIcon} from '@yiisoft/yii-dev-panel-sdk/Component/SvgIcon/YiiIcon';
import {Config} from '@yiisoft/yii-dev-panel-sdk/Config';
import {NotificationSnackbar} from '@yiisoft/yii-dev-panel/Application/Component/NotificationSnackbar';
import * as React from 'react';
import {Fragment} from 'react';
import {ErrorBoundary} from 'react-error-boundary';
import {Outlet} from 'react-router';
import Drawer from '@mui/material/Drawer';
import Button from '@mui/material/Button';
import Collapse from '@mui/material/Collapse';
import ExpandLess from '@mui/icons-material/ExpandLess';
import ExpandMore from '@mui/icons-material/ExpandMore';
import {useBreadcrumbsContext} from '@yiisoft/yii-dev-panel/Application/Context/BreadcrumbsContext';
import BuildCircleIcon from '@mui/icons-material/BuildCircle';
import AdbIcon from '@mui/icons-material/Adb';
import LoupeIcon from '@mui/icons-material/Loupe';
import SettingsIcon from '@mui/icons-material/Settings';
import DataObjectIcon from '@mui/icons-material/DataObject';
import QueueIcon from '@mui/icons-material/Queue';
import LabelIcon from '@mui/icons-material/Label';

// TODO: replace with context and provider
const pages = [
    {name: 'Gii', link: '/gii', icon: <BuildCircleIcon />},
    {name: 'Debug', link: '/debug', icon: <AdbIcon />},
    {
        name: 'Config',
        icon: <SettingsIcon />,
        items: [
            {name: 'Configuration', link: '/inspector/config'},
            {name: 'Events', link: '/inspector/events'},
            {name: 'Routes', link: '/inspector/routes'},
        ],
    },
    {
        name: 'Inspector',
        icon: <LoupeIcon />,
        items: [
            {name: 'Tests', link: '/inspector/tests'},
            {name: 'Analyse', link: '/inspector/analyse'},
            {name: 'File Explorer', link: '/inspector/files'},
            {name: 'Translations', link: '/inspector/translations'},
            {name: 'Commands', link: '/inspector/commands'},
            {name: 'Database', link: '/inspector/database'},
            {name: 'Cache', link: '/inspector/cache'},
            {name: 'Git', link: '/inspector/git'},
            {name: 'PHP Info', link: '/inspector/phpinfo'},
            {name: 'Composer', link: '/inspector/composer'},
            {name: 'Opcache', link: '/inspector/opcache'},
        ],
    },
    {name: 'Open API', link: '/open-api', icon: <DataObjectIcon />},
    {name: 'Frames', link: '/frames', icon: <QueueIcon />},
    // Uncomment to debug shared components
    // {name: 'Shared', link: '/shared'},
];

const repositoryUrl = 'https://github.com/yiisoft/yii-dev-panel';

export const Layout = React.memo(({children}: React.PropsWithChildren) => {
    const onRefreshHandler = () => {
        if ('location' in window) {
            window.location.reload();
        }
    };
    const [open, setOpen] = React.useState(false);

    const toggleDrawer = (newOpen: boolean) => () => {
        setOpen(newOpen);
    };

    const [openItems, setOpenItems] = React.useState(new Map());

    const handleToggleChildren = (object: Object) => () => {
        setOpenItems((prevMap) => {
            const newMap = new Map(prevMap);
            newMap.set(object, !(prevMap.has(object) && prevMap.get(object)));
            return newMap;
        });
    };

    const DrawerList = (
        <Box
            // onClick={toggleDrawer(false)}
            sx={{
                width: 280,
                display: 'flex',
                flexDirection: 'column',
                flexGrow: 1,
            }}
        >
            <Typography variant="h6">
                <Link href={'/'} sx={{display: 'inline-block', height: '100%', mx: 2, my: 2}}>
                    <YiiIcon />
                </Link>
                <Link
                    href={'/'}
                    sx={{
                        textDecoration: 'none',
                    }}
                >
                    Yii Dev Panel
                </Link>
            </Typography>
            <List>
                {pages.map((parentPage, index) => (
                    <Fragment key={parentPage.name}>
                        <ListItem key={parentPage.name} disablePadding>
                            <ListItemButton
                                {...('link' in parentPage
                                    ? {href: parentPage.link}
                                    : {onClick: handleToggleChildren(parentPage)})}
                            >
                                <ListItemIcon>{parentPage.icon}</ListItemIcon>
                                <ListItemText primary={parentPage.name} />
                                {'items' in parentPage && parentPage.items.length > 0 && (
                                    <ListItemSecondaryAction>
                                        {openItems.has(parentPage) && openItems.get(parentPage) ? (
                                            <ExpandLess />
                                        ) : (
                                            <ExpandMore />
                                        )}
                                    </ListItemSecondaryAction>
                                )}
                            </ListItemButton>
                        </ListItem>
                        {'items' in parentPage && parentPage.items.length > 0 && (
                            <Collapse
                                in={openItems.has(parentPage) && openItems.get(parentPage)}
                                timeout="auto"
                                unmountOnExit={false}
                            >
                                <List component="div" disablePadding>
                                    {parentPage.items.map((page, index) => (
                                        <Fragment key={page.name}>
                                            <ListItemButton href={page.link} sx={{pl: 4}}>
                                                <ListItemIcon>
                                                    <LabelIcon />
                                                </ListItemIcon>
                                                <ListItemText primary={page.name} />
                                            </ListItemButton>
                                        </Fragment>
                                    ))}
                                </List>
                            </Collapse>
                        )}
                    </Fragment>
                ))}
            </List>
            <Divider />
            <List sx={{mt: 'auto'}}>
                <ListItem disablePadding>
                    <ListItemButton onClick={onRefreshHandler}>
                        <ListItemIcon>
                            <Refresh fontSize="small" />
                        </ListItemIcon>
                        <ListItemText>Refresh page</ListItemText>
                    </ListItemButton>
                </ListItem>
                <ListItem disablePadding>
                    <ListItemButton href={repositoryUrl} target="_blank">
                        <ListItemIcon>
                            <GitHub fontSize="small" />
                        </ListItemIcon>
                        <ListItemText>Open Github</ListItemText>
                    </ListItemButton>
                </ListItem>
                <ListItem disablePadding>
                    <ListItemButton>
                        <ListItemIcon>
                            <ContentCut fontSize="small" />
                        </ListItemIcon>
                        <ListItemText>
                            Build <b>{Config.buildVersion}</b>
                        </ListItemText>
                    </ListItemButton>
                </ListItem>
            </List>
        </Box>
    );

    return (
        <>
            <CssBaseline />
            <Drawer open={open} onClose={toggleDrawer(false)}>
                {DrawerList}
            </Drawer>
            <NotificationSnackbar />
            <Container
                maxWidth="lg"
                sx={(theme) => ({
                    [theme.breakpoints.down('md')]: {px: 2},
                })}
            >
                <BreadcrumbsWrapper toggleDrawer={toggleDrawer} />
                <ErrorBoundary FallbackComponent={ErrorFallback} resetKeys={[window.location.pathname]}>
                    <Outlet />
                </ErrorBoundary>
            </Container>
            {children}
            <ScrollTopButton bottomOffset={!!children} />
        </>
    );
});

type MyBreadcrumbsType = {
    toggleDrawer: (open: boolean) => () => void;
};
const BreadcrumbsWrapper = ({toggleDrawer}: MyBreadcrumbsType) => {
    const context = useBreadcrumbsContext();
    const breadcrumbs = context.breadcrumbs.filter(Boolean);

    const linkableBreadcrumbs = breadcrumbs.slice(0, -1);
    const lastBreadcrumb = breadcrumbs.at(-1);

    const cumulativeBreadcrumbs = linkableBreadcrumbs.reduce((acc: {href: string; title?: string}[], item) => {
        const previousHref = acc.length > 0 ? acc[acc.length - 1].href : '';
        if (typeof item === 'string') {
            acc.push({href: `${previousHref}/${item}`, title: item});
        } else {
            acc.push({href: `${previousHref}/${item.href}`, title: item.title});
        }
        return acc;
    }, []);

    return (
        <Breadcrumbs sx={{my: 2}}>
            <Button color="primary" onClick={toggleDrawer(true)}>
                Menu
            </Button>
            <Link underline="hover" color="inherit" href="/">
                Main
            </Link>
            {cumulativeBreadcrumbs.map((item) =>
                typeof item == 'string' ? (
                    <Link underline="hover" color="inherit" href={item}>
                        {item}
                    </Link>
                ) : (
                    <Link underline="hover" color="inherit" href={item.href}>
                        {item.title}
                    </Link>
                ),
            )}
            {lastBreadcrumb && (
                <Typography color="text.primary">
                    {typeof lastBreadcrumb == 'string' ? lastBreadcrumb : lastBreadcrumb.title}
                </Typography>
            )}
        </Breadcrumbs>
    );
};
