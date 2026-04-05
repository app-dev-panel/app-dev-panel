import SvgIcon, {type SvgIconProps} from '@mui/material/SvgIcon';

export const DuckIcon = (props: SvgIconProps) => (
    <SvgIcon {...props} viewBox="0 0 128 128">
        <style>
            {`
                @keyframes duck-wave-1 {
                    0%, 100% { d: path("M8 106C18 98 30 98 40 106C50 114 62 114 72 106C82 98 94 98 104 106C114 114 120 114 124 110"); }
                    50% { d: path("M8 106C18 114 30 114 40 106C50 98 62 98 72 106C82 114 94 114 104 106C114 98 120 98 124 110"); }
                }
                @keyframes duck-wave-2 {
                    0%, 100% { d: path("M4 112C14 106 26 106 36 112C46 118 58 118 68 112C78 106 90 106 100 112C110 118 118 118 124 114"); }
                    50% { d: path("M4 112C14 118 26 118 36 112C46 106 58 106 68 112C78 118 90 118 100 112C110 106 118 106 124 114"); }
                }
                button:hover .duck-wave-1 {
                    animation: duck-wave-1 1.2s ease-in-out infinite;
                }
                button:hover .duck-wave-2 {
                    animation: duck-wave-2 1.2s ease-in-out infinite;
                    animation-delay: 0.3s;
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
            className="duck-wave-1"
            d="M8 106C18 98 30 98 40 106C50 114 62 114 72 106C82 98 94 98 104 106C114 114 120 114 124 110"
            stroke="#60A5FA"
            strokeWidth="3.5"
            strokeLinecap="round"
            fill="none"
        />
        <path
            className="duck-wave-2"
            d="M4 112C14 106 26 106 36 112C46 118 58 118 68 112C78 106 90 106 100 112C110 118 118 118 124 114"
            stroke="#93C5FD"
            strokeWidth="3"
            strokeLinecap="round"
            fill="none"
        />
    </SvgIcon>
);
