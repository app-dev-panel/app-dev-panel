import {useGetStatusQuery, useSetTimeoutMutation} from '@app-dev-panel/panel/Module/Llm/API/Llm';
import ArrowDropDownIcon from '@mui/icons-material/ArrowDropDown';
import {
    Box,
    Button,
    ButtonGroup,
    CircularProgress,
    ClickAwayListener,
    MenuItem,
    MenuList,
    Paper,
    Popper,
} from '@mui/material';
import {useCallback, useRef, useState} from 'react';

const TIMEOUT_OPTIONS = [10, 15, 20, 30, 45, 60, 90, 120];

type SendButtonProps = {label: string; onClick: () => void; disabled?: boolean; loading?: boolean};

export const SendButton = ({label, onClick, disabled, loading}: SendButtonProps) => {
    const {data: status} = useGetStatusQuery();
    const [setTimeoutApi] = useSetTimeoutMutation();
    const [open, setOpen] = useState(false);
    const anchorRef = useRef<HTMLDivElement>(null);

    const timeout = status?.timeout ?? 30;

    const handleTimeoutChange = useCallback(
        async (value: number) => {
            await setTimeoutApi({timeout: value});
            setOpen(false);
        },
        [setTimeoutApi],
    );

    return (
        <>
            <ButtonGroup variant="contained" ref={anchorRef} sx={{flexShrink: 0}}>
                <Button
                    onClick={onClick}
                    disabled={disabled || loading}
                    startIcon={loading ? <CircularProgress size={16} color="inherit" /> : undefined}
                >
                    {label}
                </Button>
                <Button
                    size="small"
                    onClick={() => setOpen((prev) => !prev)}
                    sx={{px: 0.5, minWidth: 'auto', fontSize: '11px', gap: 0}}
                >
                    <Box component="span" sx={{fontSize: '11px', fontWeight: 600}}>
                        {timeout}s
                    </Box>
                    <ArrowDropDownIcon sx={{fontSize: 16}} />
                </Button>
            </ButtonGroup>
            <Popper open={open} anchorEl={anchorRef.current} placement="bottom-end" sx={{zIndex: 1300}}>
                <ClickAwayListener onClickAway={() => setOpen(false)}>
                    <Paper variant="outlined" sx={{mt: 0.5}}>
                        <MenuList dense>
                            {TIMEOUT_OPTIONS.map((t) => (
                                <MenuItem key={t} selected={t === timeout} onClick={() => handleTimeoutChange(t)}>
                                    {t}s
                                </MenuItem>
                            ))}
                        </MenuList>
                    </Paper>
                </ClickAwayListener>
            </Popper>
        </>
    );
};
