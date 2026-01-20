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
import * as React from 'react';

type CheckoutDialog = {
    currentBranch: string;
    branches: string[];
    onCancel: () => void;
    onCheckout: (data: {branch: string}) => void;
} & DialogProps;
export const CheckoutDialog = ({open, currentBranch, branches, onCancel, onCheckout, ...rest}: CheckoutDialog) => {
    // const [forceCheckout, setForceCheckout] = React.useState(false);
    const [selectedBranch, setSelectedBranch] = React.useState<string>(currentBranch);

    const handleMaxWidthChange = (event: SelectChangeEvent<typeof selectedBranch>) => {
        setSelectedBranch(event.target.value);
    };

    // const handleFullWidthChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    //     setForceCheckout(event.target.checked);
    // };

    return (
        <Dialog fullWidth open={open} onClose={onCancel} {...rest}>
            <DialogTitle>Checkout</DialogTitle>
            <DialogContent>
                <DialogContentText>Select a branch to checkout</DialogContentText>
                <Box
                    noValidate
                    component="form"
                    sx={{
                        display: 'flex',
                        flexDirection: 'row',
                        justifyContent: 'space-between',
                        m: 'auto',
                    }}
                >
                    <FormControl sx={{mt: 2, flexGrow: 0.9}}>
                        <InputLabel htmlFor="max-width">Branch</InputLabel>
                        <Select
                            autoFocus
                            fullWidth
                            value={selectedBranch}
                            onChange={handleMaxWidthChange}
                            label="Branch"
                        >
                            {branches.map((branch, index) => (
                                <MenuItem key={index} value={branch}>
                                    {branch}
                                </MenuItem>
                            ))}
                        </Select>
                    </FormControl>
                    {/*<FormControlLabel*/}
                    {/*    sx={{mt: 1}}*/}
                    {/*    control={<Switch checked={forceCheckout} onChange={handleFullWidthChange} />}*/}
                    {/*    label="Force checkout"*/}
                    {/*/>*/}
                </Box>
            </DialogContent>
            <DialogActions>
                <Button variant="outlined" color="error" onClick={onCancel}>
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
