import {ButtonPropsColorOverrides} from '@mui/material/Button/Button';
import {OverridableStringUnion} from '@mui/types';

export type MuiColor = OverridableStringUnion<
    'inherit' | 'primary' | 'secondary' | 'success' | 'error' | 'info' | 'warning',
    ButtonPropsColorOverrides
>;
