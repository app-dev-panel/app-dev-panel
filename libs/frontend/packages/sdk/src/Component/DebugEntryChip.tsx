import {DebugEntry} from '@app-dev-panel/sdk/API/Debug/Debug';
import {DebugChip} from '@app-dev-panel/sdk/Component/DebugChip';
import {buttonColorConsole, buttonColorHttp} from '@app-dev-panel/sdk/Helper/buttonColor';
import {isDebugEntryAboutConsole, isDebugEntryAboutWeb} from '@app-dev-panel/sdk/Helper/debugEntry';
import TerminalIcon from '@mui/icons-material/Terminal';

type DebugChipProps = {entry: DebugEntry};
export const DebugEntryChip = ({entry}: DebugChipProps) => {
    if (isDebugEntryAboutConsole(entry)) {
        return (
            <DebugChip
                icon={<TerminalIcon />}
                label={entry.command?.exitCode}
                color={buttonColorConsole(Number(entry.command?.exitCode))}
            />
        );
    }
    if (isDebugEntryAboutWeb(entry)) {
        return (
            <DebugChip
                label={[entry.response?.statusCode, entry.request.method].join(' ')}
                color={buttonColorHttp(entry.response?.statusCode)}
            />
        );
    }
    return null;
};
