import {AlertColor} from '@mui/material';

export const buttonColorConsole = (code: number): AlertColor => (code === 0 ? 'success' : 'error');
export const buttonColorHttp = (status: number): AlertColor => {
    switch (true) {
        case status >= 400:
            return 'error';
        case status >= 300:
            return 'warning';
        case status >= 200:
            return 'success';
    }
    return 'info';
};
