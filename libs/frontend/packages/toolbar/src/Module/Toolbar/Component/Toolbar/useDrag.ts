import {useCallback, useEffect, useRef, useState} from 'react';

type SnapZone = 'bottom' | 'right' | 'left' | null;

type UseDragOptions = {
    onDragEnd: (snapZone: SnapZone) => void;
    onPositionChange: (x: number, y: number) => void;
    getWidgetRect: () => DOMRect | null;
    /** Size of the float widget when undocking from a snapped position */
    floatSize?: {width: number; height: number};
};

const SNAP_THRESHOLD = 40;

/**
 * Drag hook using document-level mousemove/mouseup listeners.
 * When undocking from a full-width/full-height snapped position,
 * centers the float widget under the cursor.
 */
export const useDrag = ({onDragEnd, onPositionChange, getWidgetRect, floatSize}: UseDragOptions) => {
    const [isDragging, setIsDragging] = useState(false);
    const [snapZone, setSnapZone] = useState<SnapZone>(null);

    const dragRef = useRef<{startX: number; startY: number; offsetX: number; offsetY: number} | null>(null);
    const snapZoneRef = useRef<SnapZone>(null);
    const onDragEndRef = useRef(onDragEnd);
    const onPositionChangeRef = useRef(onPositionChange);
    onDragEndRef.current = onDragEnd;
    onPositionChangeRef.current = onPositionChange;

    const fw = floatSize?.width ?? 320;
    const fh = floatSize?.height ?? 360;

    useEffect(() => {
        const onMouseMove = (e: MouseEvent) => {
            if (!dragRef.current) return;
            const x = Math.max(0, Math.min(e.clientX - dragRef.current.offsetX, window.innerWidth - fw));
            const y = Math.max(0, Math.min(e.clientY - dragRef.current.offsetY, window.innerHeight - fh));
            onPositionChangeRef.current(x, y);

            const nearBottom = window.innerHeight - y - fh < SNAP_THRESHOLD && x > SNAP_THRESHOLD;
            const nearRight = window.innerWidth - x - fw < SNAP_THRESHOLD;
            const nearLeft = x < SNAP_THRESHOLD;
            const zone: SnapZone =
                nearBottom && !nearRight && !nearLeft ? 'bottom' : nearRight ? 'right' : nearLeft ? 'left' : null;
            if (snapZoneRef.current !== zone) {
                snapZoneRef.current = zone;
                setSnapZone(zone);
            }
        };

        const onMouseUp = () => {
            if (!dragRef.current) return;
            const zone = snapZoneRef.current;
            dragRef.current = null;
            snapZoneRef.current = null;
            setIsDragging(false);
            setSnapZone(null);
            onDragEndRef.current(zone);
        };

        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup', onMouseUp);
        return () => {
            document.removeEventListener('mousemove', onMouseMove);
            document.removeEventListener('mouseup', onMouseUp);
        };
    }, [fw, fh]);

    const onMouseDown = useCallback(
        (e: React.MouseEvent) => {
            if ((e.target as HTMLElement).closest('button, [role="button"]')) return;
            const rect = getWidgetRect();
            if (!rect) return;
            e.preventDefault();

            // If widget is snapped (full-width or full-height), center float under cursor
            const isFullWidth = rect.width > window.innerWidth * 0.8;
            const isFullHeight = rect.height > window.innerHeight * 0.8;

            let offsetX: number;
            let offsetY: number;

            if (isFullWidth || isFullHeight) {
                // Undocking: place center of float widget under cursor
                offsetX = fw / 2;
                offsetY = 20; // grab near top
            } else {
                // Already floating: preserve cursor offset within widget
                offsetX = e.clientX - rect.left;
                offsetY = e.clientY - rect.top;
            }

            dragRef.current = {startX: e.clientX, startY: e.clientY, offsetX, offsetY};
            setIsDragging(true);
            snapZoneRef.current = null;
            setSnapZone(null);
        },
        [getWidgetRect, fw, fh],
    );

    return {isDragging, snapZone, dragHandleProps: {onMouseDown, style: {cursor: 'grab'} as const}};
};
