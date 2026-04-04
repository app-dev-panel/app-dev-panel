import {useCallback, useEffect, useRef, useState} from 'react';

type SnapZone = 'bottom' | 'right' | 'left' | null;

type UseDragOptions = {
    onDragEnd: (snapZone: SnapZone) => void;
    onPositionChange: (x: number, y: number) => void;
    getWidgetRect: () => DOMRect | null;
};

const SNAP_THRESHOLD = 40;

/**
 * Drag hook using document-level mousemove/mouseup listeners (like the V21 mockup).
 * More reliable than pointer capture which breaks with MUI portals.
 */
export const useDrag = ({onDragEnd, onPositionChange, getWidgetRect}: UseDragOptions) => {
    const [isDragging, setIsDragging] = useState(false);
    const [snapZone, setSnapZone] = useState<SnapZone>(null);

    const dragRef = useRef<{startX: number; startY: number; widgetStartX: number; widgetStartY: number} | null>(null);
    const snapZoneRef = useRef<SnapZone>(null);
    const onDragEndRef = useRef(onDragEnd);
    const onPositionChangeRef = useRef(onPositionChange);
    onDragEndRef.current = onDragEnd;
    onPositionChangeRef.current = onPositionChange;

    useEffect(() => {
        const onMouseMove = (e: MouseEvent) => {
            if (!dragRef.current) return;
            const dx = e.clientX - dragRef.current.startX;
            const dy = e.clientY - dragRef.current.startY;
            const x = Math.max(0, Math.min(dragRef.current.widgetStartX + dx, window.innerWidth - 280));
            const y = Math.max(0, Math.min(dragRef.current.widgetStartY + dy, window.innerHeight - 100));
            onPositionChangeRef.current(x, y);

            const nearBottom = window.innerHeight - y - 100 < SNAP_THRESHOLD && x > SNAP_THRESHOLD;
            const nearRight = window.innerWidth - x - 280 < SNAP_THRESHOLD;
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
    }, []);

    const onMouseDown = useCallback(
        (e: React.MouseEvent) => {
            if ((e.target as HTMLElement).closest('button, [role="button"]')) return;
            const rect = getWidgetRect();
            if (!rect) return;
            e.preventDefault();
            dragRef.current = {startX: e.clientX, startY: e.clientY, widgetStartX: rect.left, widgetStartY: rect.top};
            setIsDragging(true);
            snapZoneRef.current = null;
            setSnapZone(null);
        },
        [getWidgetRect],
    );

    return {isDragging, snapZone, dragHandleProps: {onMouseDown, style: {cursor: 'grab'} as const}};
};
