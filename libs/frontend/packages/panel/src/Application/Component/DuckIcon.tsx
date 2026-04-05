import SvgIcon, {type SvgIconProps} from '@mui/material/SvgIcon';
import {useEffect, useRef, useState} from 'react';

const EYE_CX = 44;
const EYE_CY = 42;
const MAX_OFFSET = 3;

export const DuckIcon = (props: SvgIconProps) => {
    const svgRef = useRef<SVGSVGElement>(null);
    const [pupil, setPupil] = useState({x: EYE_CX, y: EYE_CY});
    const frameRef = useRef<number>();

    useEffect(() => {
        const handleMouseMove = (e: MouseEvent) => {
            if (frameRef.current) cancelAnimationFrame(frameRef.current);
            frameRef.current = requestAnimationFrame(() => {
                const svg = svgRef.current;
                if (!svg) return;

                const rect = svg.getBoundingClientRect();
                const cx = rect.left + rect.width / 2;
                const cy = rect.top + rect.height / 2;

                const dx = e.clientX - cx;
                const dy = e.clientY - cy;
                const angle = Math.atan2(dy, dx);
                const dist = Math.min(Math.hypot(dx, dy) / 80, 1);

                setPupil({
                    x: EYE_CX + Math.cos(angle) * MAX_OFFSET * dist,
                    y: EYE_CY + Math.sin(angle) * MAX_OFFSET * dist,
                });
            });
        };

        window.addEventListener('mousemove', handleMouseMove);
        return () => {
            window.removeEventListener('mousemove', handleMouseMove);
            if (frameRef.current) cancelAnimationFrame(frameRef.current);
        };
    }, []);

    return (
        <SvgIcon {...props} viewBox="0 0 128 128" ref={svgRef}>
            {/* Head */}
            <circle cx="64" cy="58" r="42" fill="#FCD34D" />
            {/* Beak */}
            <path d="M24 56L6 64L24 72Z" fill="#FB923C" />
            {/* Cheek */}
            <circle cx="34" cy="76" r="6" fill="#FB923C" opacity="0.2" />
            {/* Eye white */}
            <ellipse cx={EYE_CX} cy={EYE_CY} rx="10" ry="11" fill="white" />
            {/* Pupil — follows mouse */}
            <circle cx={pupil.x} cy={pupil.y} r="6" fill="#292524" />
            {/* Eye highlight */}
            <circle cx={pupil.x - 1.5} cy={pupil.y - 2} r="2.2" fill="white" />
        </SvgIcon>
    );
};
