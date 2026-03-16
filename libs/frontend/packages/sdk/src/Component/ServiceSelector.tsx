import {Chip, FormControl, InputLabel, MenuItem, Select, SelectChangeEvent, Stack, Typography} from '@mui/material';
import {changeSelectedService} from '@yiisoft/yii-dev-panel-sdk/API/Application/ApplicationContext';
import {ServiceDescriptor, useGetServicesQuery} from '@yiisoft/yii-dev-panel-sdk/API/Services/api';
import {useCallback} from 'react';
import {useDispatch, useSelector} from 'react-redux';

type RootState = {application: {selectedService: string}};

export const ServiceSelector = () => {
    const dispatch = useDispatch();
    const selectedService = useSelector((state: RootState) => state.application.selectedService);
    const {data: services} = useGetServicesQuery(undefined, {pollingInterval: 10_000});

    const handleChange = useCallback(
        (event: SelectChangeEvent<string>) => {
            dispatch(changeSelectedService(event.target.value));
        },
        [dispatch],
    );

    if (!services || services.length === 0) {
        return null;
    }

    return (
        <FormControl size="small" sx={{minWidth: 180}}>
            <InputLabel id="service-selector-label">Service</InputLabel>
            <Select
                labelId="service-selector-label"
                value={selectedService}
                label="Service"
                onChange={handleChange}
                renderValue={(value) => {
                    if (value === 'local') {
                        return 'Local (PHP)';
                    }
                    const svc = services.find((s: ServiceDescriptor) => s.service === value);
                    return svc ? `${svc.service} (${svc.language})` : value;
                }}
            >
                <MenuItem value="local">
                    <Stack direction="row" spacing={1} alignItems="center">
                        <Typography>Local</Typography>
                        <Chip label="PHP" size="small" color="primary" variant="outlined" />
                    </Stack>
                </MenuItem>
                {services.map((svc: ServiceDescriptor) => (
                    <MenuItem key={svc.service} value={svc.service} disabled={!svc.online}>
                        <Stack direction="row" spacing={1} alignItems="center">
                            <Typography>{svc.service}</Typography>
                            <Chip label={svc.language} size="small" color="secondary" variant="outlined" />
                            <Chip
                                label={svc.online ? 'online' : 'offline'}
                                size="small"
                                color={svc.online ? 'success' : 'error'}
                                variant="filled"
                            />
                        </Stack>
                    </MenuItem>
                ))}
            </Select>
        </FormControl>
    );
};
