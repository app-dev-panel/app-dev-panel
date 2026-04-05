import SvgIcon, {type SvgIconProps} from '@mui/material/SvgIcon';
import {useEffect, useRef, useState} from 'react';

const HEAD_OFFSET = 4;
const EYE_EXTRA = 2;

export const DuckIcon = (props: SvgIconProps) => {
    const svgRef = useRef<SVGSVGElement>(null);
    const [offset, setOffset] = useState({x: 0, y: 0});
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

                setOffset({
                    x: Math.cos(angle) * dist,
                    y: Math.sin(angle) * dist,
                });
            });
        };

        window.addEventListener('mousemove', handleMouseMove);
        return () => {
            window.removeEventListener('mousemove', handleMouseMove);
            if (frameRef.current) cancelAnimationFrame(frameRef.current);
        };
    }, []);

    const hx = offset.x * HEAD_OFFSET;
    const hy = offset.y * HEAD_OFFSET;
    const ex = offset.x * (HEAD_OFFSET + EYE_EXTRA);
    const ey = offset.y * (HEAD_OFFSET + EYE_EXTRA);

    return (
        <SvgIcon {...props} viewBox="0 0 128 128" ref={svgRef}>
            {/* Head — shifts toward mouse */}
            <circle cx={64 + hx} cy={58 + hy} r="42" fill="#FCD34D" />
            {/* Beak */}
            <path d={`M${24 + hx} ${56 + hy}L${6 + hx} ${64 + hy}L${24 + hx} ${72 + hy}Z`} fill="#FB923C" />
            {/* Cheek */}
            <circle cx={34 + hx} cy={76 + hy} r="6" fill="#FB923C" opacity="0.2" />
            {/* Eye white */}
            <ellipse cx={44 + hx} cy={42 + hy} rx="10" ry="11" fill="white" />
            {/* Pupil — shifts even more than head */}
            <circle cx={44 + ex} cy={42 + ey} r="6" fill="#292524" />
            {/* Eye highlight */}
            <circle cx={42.5 + ex} cy={40 + ey} r="2.2" fill="white" />
        </SvgIcon>
    );
};
