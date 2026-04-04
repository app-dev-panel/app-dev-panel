import {Box} from '@mui/material';
import {useCallback, useRef} from 'react';

type ResizeGripProps = {onResize: (deltaX: number, deltaY: number) => void; onResizeEnd: () => void};

export const ResizeGrip = ({onResize, onResizeEnd}: ResizeGripProps) => {
    const dragRef = useRef<{startX: number; startY: number} | null>(null);

    const onPointerDown = useCallback((e: React.PointerEvent) => {
        e.preventDefault();
        e.stopPropagation();
        const target = e.currentTarget as HTMLElement;
        target.setPointerCapture(e.pointerId);
        dragRef.current = {startX: e.clientX, startY: e.clientY};
    }, []);

    const onPointerMove = useCallback(
        (e: React.PointerEvent) => {
            if (!dragRef.current) return;
            const dx = e.clientX - dragRef.current.startX;
            const dy = e.clientY - dragRef.current.startY;
            dragRef.current = {startX: e.clientX, startY: e.clientY};
            onResize(dx, dy);
        },
        [onResize],
    );

    const onPointerUp = useCallback(
        (e: React.PointerEvent) => {
            if (!dragRef.current) return;
            (e.currentTarget as HTMLElement).releasePointerCapture(e.pointerId);
            dragRef.current = null;
            onResizeEnd();
        },
        [onResizeEnd],
    );

    return (
        <Box
            onPointerDown={onPointerDown}
            onPointerMove={onPointerMove}
            onPointerUp={onPointerUp}
            sx={{
                position: 'absolute',
                top: 0,
                left: 0,
                width: 18,
                height: 18,
                cursor: 'nw-resize',
                zIndex: 5,
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                opacity: 0.35,
                '&:hover': {opacity: 0.7},
            }}
        >
            <svg width="10" height="10" viewBox="0 0 10 10">
                <line x1="1" y1="10" x2="10" y2="1" stroke="currentColor" strokeWidth="1.2" />
                <line x1="4" y1="10" x2="10" y2="4" stroke="currentColor" strokeWidth="1.2" />
                <line x1="7" y1="10" x2="10" y2="7" stroke="currentColor" strokeWidth="1.2" />
            </svg>
        </Box>
    );
};
