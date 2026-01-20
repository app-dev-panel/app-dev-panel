import {CircularProgress, FormControlLabel, Switch} from '@mui/material';
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
import Select from '@mui/material/Select';
import {
    useGetComposerInspectQuery,
    usePostComposerRequirePackageMutation,
} from '@yiisoft/yii-dev-panel/Module/Inspector/API/Inspector';
import {useState} from 'react';

type SwitchDialog = {
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
}: SwitchDialog) => {
    const getComposerInspectQuery = useGetComposerInspectQuery(packageName as string, {
        skip: packageName == null,
    });
    const [selectedVersion, setSelectedVersion] = useState<string | null>(installedVersion);
    const [isDev, setIsDev] = useState<boolean>(isDevPackage);
    const [postComposerRequirePackage, postComposerRequirePackageInfo] = usePostComposerRequirePackageMutation();

    const onSwitchHandler = async (packageName: string, selectedVersion: string | null) => {
        const result = await postComposerRequirePackage({packageName, version: selectedVersion, isDev});
        console.log(result);
        onSwitch();
    };
    const onDevChanged = () => {
        setIsDev((v) => !v);
    };

    return (
        <Dialog fullWidth open={open} onClose={onClose} {...rest}>
            <DialogTitle>Switch version for "{packageName}"</DialogTitle>
            <DialogContent>
                <DialogContentText>Select a version to switch</DialogContentText>
                <DialogContentText>Installed version: {installedVersion}</DialogContentText>
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
                    <FormControl disabled={postComposerRequirePackageInfo.isLoading} sx={{mt: 2, flexGrow: 0.9}}>
                        <InputLabel htmlFor="max-width">Versions</InputLabel>
                        <Select
                            autoFocus
                            fullWidth
                            value={selectedVersion}
                            onChange={(e) => {
                                setSelectedVersion(e.target.value);
                            }}
                            label="Version"
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
            <DialogActions>
                <Button
                    variant="outlined"
                    color="error"
                    disabled={postComposerRequirePackageInfo.isLoading}
                    onClick={onClose}
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
