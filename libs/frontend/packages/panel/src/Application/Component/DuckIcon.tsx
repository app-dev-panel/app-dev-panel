import SvgIcon, {type SvgIconProps} from '@mui/material/SvgIcon';

export const DuckIcon = (props: SvgIconProps) => (
    <SvgIcon {...props} viewBox="0 0 128 128">
        <style>
            {`
                @keyframes duck-wave {
                    0%, 100% { d: path("M16 108C24 104 32 104 40 108C48 112 56 112 64 108C72 104 80 104 88 108C96 112 104 112 112 108"); }
                    50% { d: path("M16 108C24 112 32 112 40 108C48 104 56 104 64 108C72 112 80 112 88 108C96 104 104 104 112 108"); }
                }
                button:hover .duck-wave-path {
                    animation: duck-wave 1s ease-in-out infinite;
                }
            `}
        </style>
        <ellipse cx="70" cy="82" rx="42" ry="30" fill="#FCD34D" />
        <circle cx="44" cy="44" r="24" fill="#FCD34D" />
        <path d="M24 40L10 44L24 48Z" fill="#FB923C" />
        <circle cx="38" cy="38" r="3.5" fill="#292524" />
        <circle cx="37" cy="37" r="1.2" fill="white" />
        <path d="M56 74C64 66 82 68 94 78" stroke="#FBBF24" strokeWidth="3" strokeLinecap="round" fill="none" />
        <path
            className="duck-wave-path"
            d="M16 108C24 104 32 104 40 108C48 112 56 112 64 108C72 104 80 104 88 108C96 112 104 112 112 108"
            stroke="#93C5FD"
            strokeWidth="2.5"
            strokeLinecap="round"
            fill="none"
        />
    </SvgIcon>
);
