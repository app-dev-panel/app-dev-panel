import {Clear} from '@mui/icons-material';
import {FormControl, IconButton, Input, InputAdornment, InputLabel} from '@mui/material';
import * as React from 'react';
import {useRef} from 'react';

export type FilterInputProps = {
    value?: string;
    onChange: (value: string) => void;
};
export const FilterInput = React.memo(
    (props: FilterInputProps) => {
        const {onChange, value = ''} = props;
        const inputRef = useRef<HTMLInputElement>();
        const timeoutIdRef = useRef<ReturnType<typeof setTimeout> | undefined>(undefined);

        return (
            <FormControl sx={{mb: 1}} variant="standard">
                <InputLabel htmlFor="filter">Filter</InputLabel>
                <Input
                    id="filter"
                    autoFocus
                    inputRef={inputRef}
                    defaultValue={value}
                    onChange={(e) => {
                        clearTimeout(timeoutIdRef.current);
                        timeoutIdRef.current = setTimeout(() => {
                            onChange(e.target.value);
                        }, 300);
                    }}
                    endAdornment={
                        <InputAdornment position="end">
                            <IconButton
                                onClick={async () => {
                                    onChange('');
                                    if (inputRef.current) {
                                        inputRef.current.value = '';
                                    }
                                }}
                                edge="end"
                            >
                                <Clear />
                            </IconButton>
                        </InputAdornment>
                    }
                />
            </FormControl>
        );
    },
    (prevProps, nextProps) => prevProps.onChange === nextProps.onChange,
);
