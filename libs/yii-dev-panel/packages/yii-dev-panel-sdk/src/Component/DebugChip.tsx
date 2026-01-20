import {AlertColor, Chip} from '@mui/material';
import {ReactElement} from 'react';

type DebugChip = {
    label?: string | number;
    icon?: ReactElement;
    color?: AlertColor;
};
export const DebugChip = ({label, icon, color}: DebugChip) => {
    return <Chip sx={{borderRadius: '5px 5px', margin: '0 2px'}} icon={icon} label={label} color={color} />;
};
