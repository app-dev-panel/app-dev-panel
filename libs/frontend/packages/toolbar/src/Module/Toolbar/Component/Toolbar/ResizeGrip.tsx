import {Box} from '@mui/material';
import {useCallback, useEffect, useRef} from 'react';

type ResizeGripProps = {onResize: (deltaX: number, deltaY: number) => void; onResizeEnd: () => void};

/**
 * Resize grip (top-left corner, 3 diagonal lines).
 * Uses document-level mousemove/mouseup for reliable tracking.
 */
export const ResizeGrip = ({onResize, onResizeEnd}: ResizeGripProps) => {
    const onResizeRef = useRef(onResize);
    const onResizeEndRef = useRef(onResizeEnd);
    const activeRef = useRef(false);
    onResizeRef.current = onResize;
    onResizeEndRef.current = onResizeEnd;

    const lastPos = useRef({x: 0, y: 0});

    useEffect(() => {
        const onMouseMove = (e: MouseEvent) => {
            if (!activeRef.current) return;
            const dx = e.clientX - lastPos.current.x;
            const dy = e.clientY - lastPos.current.y;
            lastPos.current = {x: e.clientX, y: e.clientY};
            onResizeRef.current(dx, dy);
        };

        const onMouseUp = () => {
            if (!activeRef.current) return;
            activeRef.current = false;
            onResizeEndRef.current();
        };

        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup', onMouseUp);
        return () => {
            document.removeEventListener('mousemove', onMouseMove);
            document.removeEventListener('mouseup', onMouseUp);
        };
    }, []);

    const onMouseDown = useCallback((e: React.MouseEvent) => {
        e.preventDefault();
        e.stopPropagation();
        lastPos.current = {x: e.clientX, y: e.clientY};
        activeRef.current = true;
    }, []);

    return (
        <Box
            onMouseDown={onMouseDown}
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
