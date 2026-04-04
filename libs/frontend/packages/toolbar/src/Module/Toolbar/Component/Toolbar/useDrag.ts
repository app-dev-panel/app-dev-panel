import {useCallback, useRef, useState} from 'react';

type SnapZone = 'bottom' | 'right' | 'left' | null;

type UseDragOptions = {
    onDragEnd: (snapZone: SnapZone) => void;
    onPositionChange: (x: number, y: number) => void;
    getWidgetRect: () => DOMRect | null;
};

const SNAP_THRESHOLD = 40;

export const useDrag = ({onDragEnd, onPositionChange, getWidgetRect}: UseDragOptions) => {
    const [isDragging, setIsDragging] = useState(false);
    const [snapZone, setSnapZone] = useState<SnapZone>(null);
    const dragRef = useRef<{startX: number; startY: number; widgetStartX: number; widgetStartY: number} | null>(null);
    const snapZoneRef = useRef<SnapZone>(null);
    const onDragEndRef = useRef(onDragEnd);
    onDragEndRef.current = onDragEnd;

    const onPointerDown = useCallback(
        (e: React.PointerEvent) => {
            const rect = getWidgetRect();
            if (!rect) return;
            e.preventDefault();
            e.stopPropagation();
            (e.currentTarget as HTMLElement).setPointerCapture(e.pointerId);
            dragRef.current = {startX: e.clientX, startY: e.clientY, widgetStartX: rect.left, widgetStartY: rect.top};
            setIsDragging(true);
            snapZoneRef.current = null;
            setSnapZone(null);
        },
        [getWidgetRect],
    );

    const onPointerMove = useCallback(
        (e: React.PointerEvent) => {
            if (!dragRef.current) return;
            const dx = e.clientX - dragRef.current.startX;
            const dy = e.clientY - dragRef.current.startY;
            const x = Math.max(0, Math.min(dragRef.current.widgetStartX + dx, window.innerWidth - 260));
            const y = Math.max(0, Math.min(dragRef.current.widgetStartY + dy, window.innerHeight - 100));
            onPositionChange(x, y);

            const nearBottom = window.innerHeight - y - 100 < SNAP_THRESHOLD && x > SNAP_THRESHOLD;
            const nearRight = window.innerWidth - x - 260 < SNAP_THRESHOLD;
            const nearLeft = x < SNAP_THRESHOLD;

            const zone: SnapZone =
                nearBottom && !nearRight && !nearLeft ? 'bottom' : nearRight ? 'right' : nearLeft ? 'left' : null;
            if (snapZoneRef.current !== zone) {
                snapZoneRef.current = zone;
                setSnapZone(zone);
            }
        },
        [onPositionChange],
    );

    const onPointerUp = useCallback((e: React.PointerEvent) => {
        if (!dragRef.current) return;
        (e.currentTarget as HTMLElement).releasePointerCapture(e.pointerId);
        const zone = snapZoneRef.current;
        dragRef.current = null;
        snapZoneRef.current = null;
        setIsDragging(false);
        setSnapZone(null);
        onDragEndRef.current(zone);
    }, []);

    return {
        isDragging,
        snapZone,
        dragHandleProps: {onPointerDown, onPointerMove, onPointerUp, style: {cursor: 'grab'} as const},
    };
};
