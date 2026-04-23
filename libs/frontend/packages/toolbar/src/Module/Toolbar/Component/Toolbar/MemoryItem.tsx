import {DebugEntry} from '@app-dev-panel/sdk/API/Debug/Debug';
import {CollectorsMap} from '@app-dev-panel/sdk/Helper/collectors';
import {isDebugEntryAboutWeb} from '@app-dev-panel/sdk/Helper/debugEntry';
import {formatBytes} from '@app-dev-panel/sdk/Helper/formatBytes';
import {openInNewTabOnModifier} from '@app-dev-panel/sdk/Helper/openInNewTabOnModifier';
import {panelPagePath} from '@app-dev-panel/sdk/Helper/panelMountPath';
import MemoryIcon from '@mui/icons-material/Memory';
import {Chip, Tooltip} from '@mui/material';

type MemoryItemProps = {data: DebugEntry; iframeUrlHandler: (url: string) => void};

export const MemoryItem = ({data, iframeUrlHandler}: MemoryItemProps) => {
    const collector = isDebugEntryAboutWeb(data)
        ? CollectorsMap.WebAppInfoCollector
        : CollectorsMap.ConsoleAppInfoCollector;
    const peakUsage = (data.web || data.console).memory.peakUsage;

    return (
        <Tooltip title={`${peakUsage.toLocaleString(undefined)} bytes`} arrow>
            <Chip
                icon={<MemoryIcon sx={{fontSize: '16px !important'}} />}
                label={formatBytes(peakUsage)}
                size="small"
                variant="outlined"
                onClick={(e) => {
                    const url = panelPagePath(`?collector=${encodeURIComponent(collector)}&debugEntry=${data.id}`);
                    if (openInNewTabOnModifier(e, url)) return;
                    iframeUrlHandler(url);
                    e.stopPropagation();
                    e.preventDefault();
                }}
                sx={{
                    height: 32,
                    borderRadius: 1,
                    fontSize: 12,
                    fontFamily: "'JetBrains Mono', monospace",
                    cursor: 'pointer',
                }}
            />
        </Tooltip>
    );
};
