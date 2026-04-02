import {
    useGetComposerInspectQuery,
    usePostComposerRequirePackageMutation,
} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {Close} from '@mui/icons-material';
import {CircularProgress, FormControlLabel, IconButton, Switch, Typography} from '@mui/material';
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import Dialog, {DialogProps} from '@mui/material/Dialog';
import DialogActions from '@mui/material/DialogActions';
import DialogContent from '@mui/material/DialogContent';
import DialogTitle from '@mui/material/DialogTitle';
import FormControl from '@mui/material/FormControl';
import InputLabel from '@mui/material/InputLabel';
import MenuItem from '@mui/material/MenuItem';
import Select from '@mui/material/Select';
import {useState} from 'react';

type SwitchDialogProps = {
    installedVersion?: null | string;
    packageName: string;
    isDev: boolean;
    onClose: () => void;
    onSwitch: () => void;
} & DialogProps;
export const SwitchDialog = ({
    open,
    isDev: isDevPackage,
    packageName,
    installedVersion = null,
    onClose,
    onSwitch,
    ...rest
}: SwitchDialogProps) => {
    const getComposerInspectQuery = useGetComposerInspectQuery(packageName as string, {skip: packageName == null});
    const [selectedVersion, setSelectedVersion] = useState<string | null>(installedVersion);
    const [isDev, setIsDev] = useState<boolean>(isDevPackage);
    const [postComposerRequirePackage, postComposerRequirePackageInfo] = usePostComposerRequirePackageMutation();

    const onSwitchHandler = async (packageName: string, selectedVersion: string | null) => {
        await postComposerRequirePackage({packageName, version: selectedVersion, isDev});
        onSwitch();
    };
    const onDevChanged = () => {
        setIsDev((v) => !v);
    };

    return (
        <Dialog fullWidth open={open} onClose={onClose} {...rest}>
            <DialogTitle sx={{display: 'flex', alignItems: 'center', justifyContent: 'space-between', pb: 1}}>
                <Box sx={{minWidth: 0}}>
                    <Typography variant="h6" component="span" sx={{fontWeight: 600}}>
                        Switch version
                    </Typography>
                    <Typography variant="body2" color="text.secondary" sx={{mt: 0.5}}>
                        {packageName}
                    </Typography>
                </Box>
                <IconButton size="small" onClick={onClose} aria-label="close" sx={{color: 'text.secondary'}}>
                    <Close fontSize="small" />
                </IconButton>
            </DialogTitle>
            <DialogContent dividers>
                <Typography variant="body2" color="text.secondary" sx={{mb: 2}}>
                    Installed version: {installedVersion ?? 'unknown'}
                </Typography>
                <Box
                    noValidate
                    component="form"
                    sx={{display: 'flex', flexDirection: 'row', justifyContent: 'space-between', m: 'auto'}}
                >
                    <FormControl disabled={postComposerRequirePackageInfo.isLoading} sx={{flexGrow: 0.9}}>
                        <InputLabel htmlFor="version-select">Versions</InputLabel>
                        <Select
                            autoFocus
                            fullWidth
                            value={selectedVersion}
                            onChange={(e) => {
                                setSelectedVersion(e.target.value);
                            }}
                            label="Version"
                            id="version-select"
                        >
                            {getComposerInspectQuery.data &&
                                getComposerInspectQuery.data.result.versions.map((version: string, index: number) => (
                                    <MenuItem key={index} value={version}>
                                        {version}
                                    </MenuItem>
                                ))}
                        </Select>
                    </FormControl>
                    <FormControlLabel
                        sx={{mt: 1}}
                        control={<Switch checked={isDev} onChange={onDevChanged} />}
                        label="--dev"
                    />
                </Box>
            </DialogContent>
            <DialogActions sx={{px: 3, py: 1.5}}>
                <Button
                    variant="text"
                    color="inherit"
                    disabled={postComposerRequirePackageInfo.isLoading}
                    onClick={onClose}
                    sx={{color: 'text.secondary'}}
                >
                    Close
                </Button>
                <Button
                    variant="contained"
                    color="primary"
                    disabled={postComposerRequirePackageInfo.isLoading}
                    endIcon={
                        postComposerRequirePackageInfo.isLoading ? <CircularProgress size={24} color="info" /> : null
                    }
                    onClick={() => {
                        onSwitchHandler(packageName, selectedVersion);
                    }}
                >
                    Switch
                </Button>
            </DialogActions>
        </Dialog>
    );
};
