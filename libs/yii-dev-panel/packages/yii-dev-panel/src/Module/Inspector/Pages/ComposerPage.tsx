import {Button, Divider, List, ListItem, ListItemSecondaryAction, Tab, Tabs} from '@mui/material';
import Box from '@mui/material/Box';
import ListItemText from '@mui/material/ListItemText';
import {JsonRenderer} from '@yiisoft/yii-dev-panel-sdk/Component/JsonRenderer';
import {useGetComposerQuery} from '@yiisoft/yii-dev-panel/Module/Inspector/API/Inspector';
import {SwitchDialog} from '@yiisoft/yii-dev-panel/Module/Inspector/Component/Composer/SwitchDialog';
import * as React from 'react';
import {SyntheticEvent, useMemo, useState} from 'react';
import {useBreadcrumbs} from '@yiisoft/yii-dev-panel/Application/Context/BreadcrumbsContext';

type TabPanelProps = {
    children?: React.ReactNode;
    index: number;
    value: number;
};

function TabPanel(props: TabPanelProps) {
    const {children, value, index, ...other} = props;

    return (
        <div role="tabpanel" hidden={value !== index} {...other}>
            {value === index && <Box sx={{p: 3}}>{children}</Box>}
        </div>
    );
}

const isPlatform = (packageName: string) => !packageName.includes('/');
type PackageItemProps = {
    packageName: string;
    version: string;
    onClick: (packageName: string) => void;
};
const PackageItem = React.memo(({packageName, version, onClick}: PackageItemProps) => {
    return (
        <ListItem disablePadding sx={{display: 'block'}}>
            <ListItemText primary={packageName} secondary={version} />
            {!isPlatform(packageName) && (
                <ListItemSecondaryAction>
                    <Button onClick={() => onClick(packageName)} color="primary">
                        Switch
                    </Button>
                </ListItemSecondaryAction>
            )}
        </ListItem>
    );
});

export const ComposerPage = () => {
    const getComposerQuery = useGetComposerQuery();
    const [value, setValue] = useState(0);
    const [showSwitchDialog, setShowSwitchDialog] = useState(false);
    const [selectedPackage, setSelectedPackage] = useState<string | null>(null);
    const [isDev, setIsDev] = useState<boolean>(false);

    const handleChange = (event: SyntheticEvent, newValue: number) => setValue(newValue);
    const installedVersions = useMemo(() => {
        const packages: Record<string, string> = {};

        if (!getComposerQuery.data || !getComposerQuery.data.lock) {
            return packages;
        }
        getComposerQuery.data.lock.packages.concat(getComposerQuery.data.lock['packages-dev']).forEach((value) => {
            packages[value.name] = value.version;
        });
        return packages;
    }, [getComposerQuery.data]);
    const onClickHandler = (name: string) => {
        setSelectedPackage(name);
        setShowSwitchDialog(true);
        setIsDev(false);
    };

    const onClickDevHandler = (name: string) => {
        setSelectedPackage(name);
        setShowSwitchDialog(true);
        setIsDev(true);
    };

    useBreadcrumbs(() => ['Inspector', 'Composer']);

    return (
        <Box sx={{width: '100%'}}>
            <Box sx={{borderBottom: 1, borderColor: 'divider'}}>
                <Tabs value={value} onChange={handleChange}>
                    <Tab label="Packages" />
                    <Tab label="composer.json" />
                    <Tab label="composer.lock" />
                </Tabs>
            </Box>
            <TabPanel value={value} index={0}>
                <Box display="flex">
                    <List sx={{width: '100%'}}>
                        <Divider>Require</Divider>
                        {getComposerQuery.data &&
                            Object.entries(getComposerQuery.data.json.require).map(([name, version], index) => (
                                <PackageItem
                                    key={index}
                                    packageName={name}
                                    version={
                                        name in installedVersions
                                            ? `Required: ${version}, Installed: ${installedVersions[name]}`
                                            : `${version}`
                                    }
                                    onClick={onClickHandler}
                                />
                            ))}
                    </List>
                    <List sx={{width: '100%'}}>
                        <Divider>Require Dev</Divider>
                        {getComposerQuery.data &&
                            Object.entries(getComposerQuery.data.json['require-dev']).map(([name, version], index) => (
                                <PackageItem
                                    key={index}
                                    packageName={name}
                                    version={
                                        name in installedVersions
                                            ? `Required: ${version}, Installed: ${installedVersions[name]}`
                                            : `${version}`
                                    }
                                    onClick={onClickDevHandler}
                                />
                            ))}
                    </List>
                </Box>
            </TabPanel>
            <TabPanel value={value} index={1}>
                {getComposerQuery.data && <JsonRenderer value={getComposerQuery.data.json} />}
            </TabPanel>
            <TabPanel value={value} index={2}>
                {getComposerQuery.data && <JsonRenderer value={getComposerQuery.data.lock} />}
            </TabPanel>
            {showSwitchDialog && (
                <SwitchDialog
                    packageName={selectedPackage as string}
                    installedVersion={
                        selectedPackage && selectedPackage in installedVersions
                            ? installedVersions[selectedPackage]
                            : null
                    }
                    open={true}
                    isDev={isDev}
                    onClose={() => setShowSwitchDialog(false)}
                    onSwitch={() => setShowSwitchDialog(false)}
                />
            )}
        </Box>
    );
};
