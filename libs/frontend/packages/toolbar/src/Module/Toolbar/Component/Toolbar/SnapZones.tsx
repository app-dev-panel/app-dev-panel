import {Box, Portal} from '@mui/material';

type SnapZonesProps = {activeZone: 'bottom' | 'right' | 'left' | null};

export const SnapZones = ({activeZone}: SnapZonesProps) => (
    <Portal>
        {(['bottom', 'right', 'left'] as const).map((zone) => (
            <Box
                key={zone}
                sx={{
                    position: 'fixed',
                    zIndex: 1299,
                    pointerEvents: 'none',
                    opacity: activeZone === zone ? 0.15 : 0,
                    transition: 'opacity 150ms',
                    borderRadius: '4px',
                    bgcolor: 'primary.main',
                    ...(zone === 'bottom' && {bottom: 0, left: '5%', right: '5%', height: 6}),
                    ...(zone === 'right' && {top: '5%', right: 0, bottom: '5%', width: 6}),
                    ...(zone === 'left' && {top: '5%', left: 0, bottom: '5%', width: 6}),
                }}
            />
        ))}
    </Portal>
);
