import SvgIcon, {type SvgIconProps} from '@mui/material/SvgIcon';
import {useEffect, useRef, useState} from 'react';

const HEAD_CX = 64;
const HEAD_CY = 58;
const MAX_TILT = 30;
const EYE_EXTRA = 2.5;

export const DuckIcon = (props: SvgIconProps) => {
    const svgRef = useRef<SVGSVGElement>(null);
    const [look, setLook] = useState({tilt: 0, ex: 0, ey: 0});
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
                const rad = Math.atan2(dy, dx);
                const angleDeg = (rad * 180) / Math.PI;
                const dist = Math.min(Math.hypot(dx, dy) / 80, 1);

                // Beak points left (180°). Calculate how much to rotate toward mouse.
                let rotation = angleDeg - 180;
                if (rotation > 180) rotation -= 360;
                if (rotation < -180) rotation += 360;

                const tilt = Math.max(-MAX_TILT, Math.min(MAX_TILT, rotation * dist));

                setLook({tilt, ex: Math.cos(rad) * EYE_EXTRA * dist, ey: Math.sin(rad) * EYE_EXTRA * dist});
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
            <g transform={`rotate(${look.tilt}, ${HEAD_CX}, ${HEAD_CY})`}>
                {/* Head */}
                <circle cx={HEAD_CX} cy={HEAD_CY} r="42" fill="#FCD34D" />
                {/* Beak — points left, rotates with head */}
                <path d="M24 56L6 64L24 72Z" fill="#FB923C" />
                {/* Cheek */}
                <circle cx="34" cy="76" r="6" fill="#FB923C" opacity="0.2" />
                {/* Eye white */}
                <ellipse cx="44" cy="42" rx="10" ry="11" fill="white" />
                {/* Pupil — extra offset for parallax */}
                <circle cx={44 + look.ex} cy={42 + look.ey} r="6" fill="#292524" />
                <circle cx={42.5 + look.ex} cy={40 + look.ey} r="2.2" fill="white" />
            </g>
        </SvgIcon>
    );
};
