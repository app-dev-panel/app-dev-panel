import {Close} from '@mui/icons-material';
import {IconButton} from '@mui/material';
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import Dialog, {DialogProps} from '@mui/material/Dialog';
import DialogActions from '@mui/material/DialogActions';
import DialogContent from '@mui/material/DialogContent';
import DialogContentText from '@mui/material/DialogContentText';
import DialogTitle from '@mui/material/DialogTitle';
import FormControl from '@mui/material/FormControl';
import InputLabel from '@mui/material/InputLabel';
import MenuItem from '@mui/material/MenuItem';
import Select, {SelectChangeEvent} from '@mui/material/Select';
import {useState} from 'react';

type CheckoutDialogProps = {
    currentBranch: string;
    branches: string[];
    onCancel: () => void;
    onCheckout: (data: {branch: string}) => void;
} & DialogProps;
export const CheckoutDialog = ({open, currentBranch, branches, onCancel, onCheckout, ...rest}: CheckoutDialogProps) => {
    const [selectedBranch, setSelectedBranch] = useState<string>(currentBranch);

    const handleBranchChange = (event: SelectChangeEvent<typeof selectedBranch>) => {
        setSelectedBranch(event.target.value);
    };

    return (
        <Dialog fullWidth open={open} onClose={onCancel} {...rest}>
            <DialogTitle sx={{display: 'flex', alignItems: 'center', justifyContent: 'space-between', pb: 1}}>
                Checkout
                <IconButton size="small" onClick={onCancel} aria-label="close" sx={{color: 'text.secondary'}}>
                    <Close fontSize="small" />
                </IconButton>
            </DialogTitle>
            <DialogContent dividers>
                <DialogContentText sx={{mb: 2}}>Select a branch to checkout</DialogContentText>
                <Box
                    noValidate
                    component="form"
                    sx={{display: 'flex', flexDirection: 'row', justifyContent: 'space-between', m: 'auto'}}
                >
                    <FormControl sx={{flexGrow: 0.9}}>
                        <InputLabel htmlFor="branch-select">Branch</InputLabel>
                        <Select
                            autoFocus
                            fullWidth
                            value={selectedBranch}
                            onChange={handleBranchChange}
                            label="Branch"
                            id="branch-select"
                        >
                            {branches.map((branch, index) => (
                                <MenuItem key={index} value={branch}>
                                    {branch}
                                </MenuItem>
                            ))}
                        </Select>
                    </FormControl>
                </Box>
            </DialogContent>
            <DialogActions sx={{px: 3, py: 1.5}}>
                <Button variant="text" color="inherit" onClick={onCancel} sx={{color: 'text.secondary'}}>
                    Cancel
                </Button>
                <Button
                    variant="contained"
                    color="primary"
                    onClick={() => {
                        onCheckout({branch: selectedBranch});
                    }}
                >
                    Checkout
                </Button>
            </DialogActions>
        </Dialog>
    );
};
