import {Tab, Tabs} from '@mui/material';
import Box from '@mui/material/Box';
import {JsonRenderer} from '@yiisoft/yii-dev-panel-sdk/Component/JsonRenderer';
import {useGetOpcacheQuery} from '@yiisoft/yii-dev-panel/Module/Inspector/API/Inspector';
import * as React from 'react';
import {SyntheticEvent, useEffect, useState} from 'react';
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

export const OpcachePage = () => {
    const getOpcacheQuery = useGetOpcacheQuery();
    const [value, setValue] = useState(0);
    const [opcache, setOpcache] = useState<any>({});
    const [jit, setJit] = useState<any>({});
    const [scripts, setScripts] = useState<any>({});
    const [configuration, setConfiguration] = useState<any>({});

    useEffect(() => {
        if (getOpcacheQuery.data) {
            const data = getOpcacheQuery.data;
            const {jit, scripts, ...opcache} = data.status;
            setOpcache(opcache);
            setJit(jit);
            setScripts(scripts);
            setConfiguration(data.configuration);
        }
    }, [getOpcacheQuery.isSuccess]);
    console.log(getOpcacheQuery.data);
    const handleChange = (event: SyntheticEvent, newValue: number) => setValue(newValue);

    useBreadcrumbs(() => ['Inspector', 'Opcache']);

    return (
        <Box sx={{width: '100%'}}>
            <Box sx={{borderBottom: 1, borderColor: 'divider'}}>
                <Tabs value={value} onChange={handleChange}>
                    <Tab label="Opcache" />
                    <Tab label="Jit" />
                    <Tab label="Scripts" />
                    <Tab label="Configuration" />
                </Tabs>
            </Box>
            <TabPanel value={value} index={0}>
                {opcache && <JsonRenderer value={opcache} />}
            </TabPanel>
            <TabPanel value={value} index={1}>
                {jit && jit && <JsonRenderer value={jit} />}
            </TabPanel>
            <TabPanel value={value} index={2}>
                {scripts && scripts && <JsonRenderer value={scripts} />}
            </TabPanel>
            <TabPanel value={value} index={3}>
                {configuration && configuration && <JsonRenderer value={configuration} />}
            </TabPanel>
        </Box>
    );
};
