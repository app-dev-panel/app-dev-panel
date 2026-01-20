import {Alert, Typography} from '@mui/material';
import Box from '@mui/material/Box';
import React, {cloneElement} from 'react';

export type InfoBoxProps = {
    title: string;
    text?: string | React.ReactElement | React.ReactElement[];
    severity: 'error' | 'info';
    icon: React.ReactElement;
};
export const InfoBox = ({text, title, icon, severity}: InfoBoxProps) => {
    return (
        <Box
            sx={{
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                width: '100%',
            }}
        >
            <>
                {cloneElement(icon, {sx: {my: 3, fontSize: 150}, color: severity})}
                <Typography component="h5" variant="h5" my={3}>
                    {title}
                </Typography>
                {text && <Alert severity={severity}>{text}</Alert>}
            </>
        </Box>
    );
};
