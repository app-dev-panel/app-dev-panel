import {SvgIcon, type SvgIconProps} from '@mui/material';

export function DockBottomIcon(props: SvgIconProps) {
    return (
        <SvgIcon viewBox="0 0 24 24" {...props}>
            <path
                fillRule="evenodd"
                clipRule="evenodd"
                d="M4.5 4A1.5 1.5 0 0 0 3 5.5v13A1.5 1.5 0 0 0 4.5 20h15a1.5 1.5 0 0 0 1.5-1.5v-13A1.5 1.5 0 0 0 19.5 4h-15Zm0 1.5h15V15h-15V5.5Z"
            />
        </SvgIcon>
    );
}
