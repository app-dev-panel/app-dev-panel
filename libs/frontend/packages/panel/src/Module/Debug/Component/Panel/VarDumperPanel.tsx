import {VarDumpValue} from '@app-dev-panel/panel/Module/Debug/Component/VarDumpValue';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FileLink} from '@app-dev-panel/sdk/Component/FileLink';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {Box, Icon, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';

type VarDumperEntry = {variable: unknown; line: string};
type VarDumperPanelProps = {data: VarDumperEntry[]};

const DumpCard = styled(Box)(({theme}) => ({
    borderBottom: `1px solid ${theme.palette.divider}`,
    padding: theme.spacing(2),
    '&:last-child': {borderBottom: 'none'},
}));

const DumpHeader = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1.5),
    marginBottom: theme.spacing(1),
}));

const IndexBadge = styled(Box)(({theme}) => ({
    width: 24,
    height: 24,
    borderRadius: '50%',
    backgroundColor: theme.palette.warning.main,
    color: theme.palette.common.white,
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    fontSize: '11px',
    fontWeight: 700,
    flexShrink: 0,
}));

const DumpBody = styled(Box)(({theme}) => ({
    padding: theme.spacing(1.5, 2),
    backgroundColor: theme.palette.mode === 'dark' ? theme.palette.background.default : theme.palette.grey[50],
    borderRadius: theme.shape.borderRadius,
    overflow: 'auto',
}));

export const VarDumperPanel = ({data}: VarDumperPanelProps) => {
    if (!data || data.length === 0) {
        return <EmptyState icon="data_object" title="No dumped variables found" />;
    }

    return (
        <Box>
            <Box sx={{display: 'flex', alignItems: 'center', gap: 2, mb: 2}}>
                <SectionTitle>{`${data.length} dump${data.length !== 1 ? 's' : ''}`}</SectionTitle>
            </Box>

            {data.map((entry, index) => (
                <DumpCard key={index}>
                    <DumpHeader>
                        <IndexBadge>{index + 1}</IndexBadge>
                        <Icon sx={{fontSize: 16, color: 'text.disabled'}}>code</Icon>
                        <FileLink path={entry.line}>
                            <Typography
                                component="span"
                                sx={{
                                    fontFamily: "'JetBrains Mono', monospace",
                                    fontSize: '12px',
                                    color: 'primary.main',
                                    textDecoration: 'none',
                                    '&:hover': {textDecoration: 'underline'},
                                }}
                            >
                                {entry.line}
                            </Typography>
                        </FileLink>
                    </DumpHeader>
                    <DumpBody>
                        <VarDumpValue value={entry.variable} />
                    </DumpBody>
                </DumpCard>
            ))}
        </Box>
    );
};
