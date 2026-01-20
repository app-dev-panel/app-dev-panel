import {AlertColor} from '@mui/material';
import {FileStateEnum} from '@yiisoft/yii-dev-panel/Module/Gii/Types/FIle.types';

export function matchSeverityByResultStatus(status: string): AlertColor {
    let result: AlertColor = 'error';
    switch (status) {
        case 'created':
            result = 'success';
            break;
        case 'overwrote':
            result = 'info';
            break;
        case 'skipped':
            result = 'warning';
            break;
    }
    return result;
}

export function matchSeverityByFileState(status: FileStateEnum): AlertColor {
    let result: AlertColor = 'error';
    switch (status) {
        case FileStateEnum.NOT_EXIST:
            result = 'success';
            break;
        case FileStateEnum.PRESENT_SAME:
            result = 'info';
            break;
        case FileStateEnum.PRESENT_DIFFERENT:
            result = 'warning';
            break;
    }
    return result;
}
