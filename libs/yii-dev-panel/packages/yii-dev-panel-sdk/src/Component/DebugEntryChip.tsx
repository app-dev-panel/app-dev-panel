import TerminalIcon from '@mui/icons-material/Terminal';
import {DebugEntry} from '@yiisoft/yii-dev-panel-sdk/API/Debug/Debug';
import {DebugChip} from '@yiisoft/yii-dev-panel-sdk/Component/DebugChip';
import {buttonColorConsole, buttonColorHttp} from '@yiisoft/yii-dev-panel-sdk/Helper/buttonColor';
import {isDebugEntryAboutConsole, isDebugEntryAboutWeb} from '@yiisoft/yii-dev-panel-sdk/Helper/debugEntry';

type DebugChipProps = {
    entry: DebugEntry;
};
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
