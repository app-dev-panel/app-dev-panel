import {FilterList} from '@mui/icons-material';
import {InputAdornment, TextField} from '@mui/material';

type FilterInputProps = {value: string; onChange: (value: string) => void; placeholder?: string};

export const FilterInput = ({value, onChange, placeholder = 'Filter...'}: FilterInputProps) => {
    return (
        <TextField
            size="small"
            placeholder={placeholder}
            value={value}
            onChange={(e) => onChange(e.target.value)}
            InputProps={{
                startAdornment: (
                    <InputAdornment position="start">
                        <FilterList sx={{fontSize: 14, color: 'text.disabled'}} />
                    </InputAdornment>
                ),
            }}
            sx={{
                width: 180,
                '& .MuiOutlinedInput-root': {fontSize: '12px', height: 26, borderRadius: 0.75},
                '& .MuiInputAdornment-root': {mr: 0},
            }}
        />
    );
};
