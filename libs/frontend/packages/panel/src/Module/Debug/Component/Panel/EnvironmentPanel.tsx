import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {Box, Chip, Tab, Tabs, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import {useMemo, useState} from 'react';

type PhpInfo = {
    version: string;
    sapi: string;
    binary: string;
    os: string;
    cwd: string | null;
    extensions: string[];
    xdebug: string | false;
    opcache: string | false;
    pcov: string | false;
    ini: {
        loaded: string | null;
        scanned: string | null;
        memory_limit: string | null;
        max_execution_time: string | null;
        display_errors: string | null;
        error_reporting: number;
    };
    zend_extensions: string[];
};

type OsInfo = {family: string; name: string; uname: string; hostname: string | null};

type GitInfo = {branch: string | null; commit: string | null; commitFull: string | null};

type EnvironmentData = {
    php: PhpInfo;
    os: OsInfo;
    git: GitInfo;
    server: Record<string, string>;
    env: Record<string, string>;
};

type EnvironmentPanelProps = {data: EnvironmentData};

const KvTable = styled('table')(({theme}) => ({
    width: '100%',
    borderCollapse: 'collapse',
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '12px',
    '& th': {
        textAlign: 'left',
        padding: theme.spacing(0.75, 1.5),
        fontWeight: 600,
        color: theme.palette.text.secondary,
        borderBottom: `1px solid ${theme.palette.divider}`,
        whiteSpace: 'nowrap',
        width: '30%',
        verticalAlign: 'top',
    },
    '& td': {
        padding: theme.spacing(0.75, 1.5),
        color: theme.palette.text.primary,
        borderBottom: `1px solid ${theme.palette.divider}`,
        wordBreak: 'break-all',
    },
    '& tr:last-child th, & tr:last-child td': {borderBottom: 'none'},
}));

const TabPanel = styled(Box)(({theme}) => ({padding: theme.spacing(2)}));

const TableContainer = styled(Box)(({theme}) => ({
    borderRadius: theme.shape.borderRadius,
    border: `1px solid ${theme.palette.divider}`,
    overflow: 'hidden',
}));

const KeyValueTable = ({entries, filter}: {entries: Array<{key: string; value: string}>; filter?: string}) => {
    const filtered = useMemo(() => {
        if (!filter) return entries;
        const lower = filter.toLowerCase();
        return entries.filter((e) => e.key.toLowerCase().includes(lower) || e.value.toLowerCase().includes(lower));
    }, [entries, filter]);

    if (filtered.length === 0) {
        return (
            <Typography sx={{color: 'text.disabled', fontSize: '13px', textAlign: 'center', py: 3}}>
                No matching entries
            </Typography>
        );
    }

    return (
        <TableContainer>
            <KvTable>
                <tbody>
                    {filtered.map((e) => (
                        <tr key={e.key}>
                            <th>{e.key}</th>
                            <td>{e.value}</td>
                        </tr>
                    ))}
                </tbody>
            </KvTable>
        </TableContainer>
    );
};

const PhpTab = ({php, os, git}: {php: PhpInfo; os: OsInfo; git: GitInfo}) => {
    const highlights: Array<{key: string; value: string}> = [
        {key: 'PHP Version', value: php.version},
        {key: 'SAPI', value: php.sapi},
        {key: 'Binary', value: php.binary},
        {key: 'OS', value: `${os.family} (${os.name})`},
        {key: 'Hostname', value: os.hostname || 'N/A'},
        {key: 'Uname', value: os.uname},
        {key: 'Working Directory', value: php.cwd || 'N/A'},
        {key: 'INI Loaded', value: php.ini?.loaded || 'N/A'},
        {key: 'Memory Limit', value: php.ini?.memory_limit || 'N/A'},
        {key: 'Max Execution Time', value: php.ini?.max_execution_time || 'N/A'},
        {key: 'Display Errors', value: php.ini?.display_errors || 'N/A'},
        {key: 'Error Reporting', value: String(php.ini?.error_reporting ?? 'N/A')},
    ];

    const gitEntries: Array<{key: string; value: string}> = [
        {key: 'Branch', value: git?.branch || 'N/A'},
        {key: 'Commit', value: git?.commitFull || 'N/A'},
    ];

    const debugExtensions = [
        {name: 'Xdebug', version: php.xdebug},
        {name: 'OPcache', version: php.opcache},
        {name: 'PCOV', version: php.pcov},
    ];

    return (
        <TabPanel>
            <SectionTitle>Runtime</SectionTitle>
            <KeyValueTable entries={highlights} />

            {(git?.branch || git?.commitFull) && (
                <>
                    <SectionTitle>Git</SectionTitle>
                    <KeyValueTable entries={gitEntries} />
                </>
            )}

            <SectionTitle>Debug Extensions</SectionTitle>
            <Box sx={{display: 'flex', gap: 1, flexWrap: 'wrap', mb: 2}}>
                {debugExtensions.map((ext) => (
                    <Chip
                        key={ext.name}
                        label={ext.version ? `${ext.name} ${ext.version}` : ext.name}
                        size="small"
                        color={ext.version ? 'success' : 'default'}
                        variant={ext.version ? 'filled' : 'outlined'}
                    />
                ))}
            </Box>

            <SectionTitle>{`Loaded Extensions (${php.extensions?.length || 0})`}</SectionTitle>
            <Box sx={{display: 'flex', gap: 0.5, flexWrap: 'wrap'}}>
                {(php.extensions || []).map((ext) => (
                    <Chip key={ext} label={ext} size="small" variant="outlined" sx={{fontSize: '11px'}} />
                ))}
            </Box>

            {php.zend_extensions?.length > 0 && (
                <>
                    <SectionTitle>Zend Extensions</SectionTitle>
                    <Box sx={{display: 'flex', gap: 0.5, flexWrap: 'wrap'}}>
                        {php.zend_extensions.map((ext) => (
                            <Chip key={ext} label={ext} size="small" variant="outlined" sx={{fontSize: '11px'}} />
                        ))}
                    </Box>
                </>
            )}
        </TabPanel>
    );
};

const DictTab = ({title, data}: {title: string; data: Record<string, string>}) => {
    const [filter, setFilter] = useState('');
    const entries = useMemo(
        () => Object.entries(data || {}).map(([key, value]) => ({key, value: String(value)})),
        [data],
    );

    return (
        <TabPanel>
            <SectionTitle action={<FilterInput value={filter} onChange={setFilter} />}>{title}</SectionTitle>
            <KeyValueTable entries={entries} filter={filter} />
        </TabPanel>
    );
};

export const EnvironmentPanel = ({data}: EnvironmentPanelProps) => {
    const [tab, setTab] = useState(0);

    if (!data) return null;

    return (
        <Box>
            <Box sx={{borderBottom: 1, borderColor: 'divider'}}>
                <Tabs
                    value={tab}
                    onChange={(_, v) => setTab(v)}
                    sx={{'& .MuiTab-root': {textTransform: 'none', minHeight: 40, fontSize: '13px', fontWeight: 600}}}
                >
                    <Tab label="PHP & OS" />
                    <Tab label={`Server (${Object.keys(data.server || {}).length})`} />
                    <Tab label={`Environment (${Object.keys(data.env || {}).length})`} />
                </Tabs>
            </Box>

            {tab === 0 && <PhpTab php={data.php} os={data.os} git={data.git} />}
            {tab === 1 && <DictTab title="Server Parameters" data={data.server} />}
            {tab === 2 && <DictTab title="Environment Variables" data={data.env} />}
        </Box>
    );
};
