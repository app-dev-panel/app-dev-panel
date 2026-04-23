import {DebugEntry} from '@app-dev-panel/sdk/API/Debug/Debug';
import {CollectorsMap} from '@app-dev-panel/sdk/Helper/collectors';
import {openInNewTabOnModifier} from '@app-dev-panel/sdk/Helper/openInNewTabOnModifier';
import {panelPagePath} from '@app-dev-panel/sdk/Helper/panelMountPath';
import {
    LOG_LEVEL_GROUP_ORDER,
    LOG_LEVEL_GROUPS,
    LogLevel,
    LogLevelGroup,
    sumLevels,
} from '@app-dev-panel/sdk/Types/LogLevel';
import DescriptionOutlinedIcon from '@mui/icons-material/DescriptionOutlined';
import {Box, Chip, Tooltip} from '@mui/material';

type LogsItemProps = {data: DebugEntry; iframeUrlHandler: (url: string) => void};

const GROUP_COLOR: Record<LogLevelGroup, string> = {
    errors: 'error.main',
    warnings: 'warning.main',
    info: 'text.secondary',
};

const buildUrl = (entryId: string, levels?: LogLevel[]) => {
    const query = `collector=${encodeURIComponent(CollectorsMap.LogCollector)}&debugEntry=${entryId}`;
    const withLevels = levels && levels.length > 0 ? `${query}&level=${levels.join(',')}` : query;
    return panelPagePath(`/debug?${withLevels}`);
};

export const LogsItem = ({data, iframeUrlHandler}: LogsItemProps) => {
    const total = data.logger?.total ?? 0;
    if (total === 0) {
        return null;
    }

    const byLevel = data.logger?.byLevel;

    const groupCounts = LOG_LEVEL_GROUP_ORDER.map((group) => ({
        group,
        count: byLevel ? sumLevels(byLevel, LOG_LEVEL_GROUPS[group]) : group === 'info' ? total : 0,
    })).filter(({count}) => count > 0);

    const segments = groupCounts.length > 0 ? groupCounts : [{group: 'info' as LogLevelGroup, count: total}];

    const tooltipLines: string[] = [];
    if (byLevel) {
        for (const group of LOG_LEVEL_GROUP_ORDER) {
            for (const level of LOG_LEVEL_GROUPS[group]) {
                const c = byLevel[level] ?? 0;
                if (c > 0) tooltipLines.push(`${c} ${level}`);
            }
        }
    }
    const tooltip = tooltipLines.length > 0 ? tooltipLines.join(', ') : `${total} log entries`;

    return (
        <Tooltip title={tooltip} arrow>
            <Chip
                icon={<DescriptionOutlinedIcon sx={{fontSize: '16px !important'}} />}
                label={
                    <Box sx={{display: 'inline-flex', alignItems: 'center', gap: 0.5}}>
                        <Box component="span">Logs</Box>
                        {segments.map(({group, count}, i) => (
                            <Box key={group} component="span" sx={{display: 'inline-flex', alignItems: 'center'}}>
                                {i > 0 && (
                                    <Box component="span" sx={{px: 0.25, color: 'text.disabled'}}>
                                        /
                                    </Box>
                                )}
                                <Box
                                    component="span"
                                    onClick={(e) => {
                                        const url = buildUrl(data.id, LOG_LEVEL_GROUPS[group]);
                                        if (openInNewTabOnModifier(e, url)) return;
                                        iframeUrlHandler(url);
                                        e.stopPropagation();
                                        e.preventDefault();
                                    }}
                                    sx={{
                                        color: GROUP_COLOR[group],
                                        fontWeight: group === 'errors' ? 700 : 600,
                                        px: 0.25,
                                        borderRadius: 0.5,
                                        cursor: 'pointer',
                                        '&:hover': {backgroundColor: 'action.hover'},
                                    }}
                                >
                                    {count}
                                </Box>
                            </Box>
                        ))}
                    </Box>
                }
                size="small"
                variant="outlined"
                onClick={(e) => {
                    const url = buildUrl(data.id);
                    if (openInNewTabOnModifier(e, url)) return;
                    iframeUrlHandler(url);
                    e.stopPropagation();
                    e.preventDefault();
                }}
                sx={{height: 32, borderRadius: 1, fontSize: 12, cursor: 'pointer'}}
            />
        </Tooltip>
    );
};
