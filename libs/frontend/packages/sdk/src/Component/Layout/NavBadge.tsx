import {styled} from '@mui/material/styles';

export type NavBadgeVariant = 'default' | 'error' | 'warning' | 'info';

export type NavBadgeSegment = {count: number; variant: NavBadgeVariant};

type NavBadgeProps = {count?: number | string; variant?: NavBadgeVariant; segments?: NavBadgeSegment[]};

const BadgeRoot = styled('span', {shouldForwardProp: (prop) => prop !== 'variant'})<{variant: NavBadgeVariant}>(
    ({theme, variant}) => ({
        fontSize: '10px',
        fontWeight: 600,
        minWidth: 18,
        height: 18,
        borderRadius: 9,
        display: 'inline-flex',
        alignItems: 'center',
        justifyContent: 'center',
        padding: '0 5px',
        fontFamily: theme.typography.fontFamily,
        ...(variant === 'error'
            ? {backgroundColor: theme.palette.error.light, color: theme.palette.error.main}
            : variant === 'warning'
              ? {backgroundColor: theme.palette.warning.light, color: theme.palette.warning.main}
              : variant === 'info'
                ? {backgroundColor: theme.palette.info.light, color: theme.palette.info.main}
                : {backgroundColor: theme.palette.action.selected, color: theme.palette.text.secondary}),
    }),
);

const SegmentedRoot = styled('span')(({theme}) => ({
    display: 'inline-flex',
    alignItems: 'center',
    height: 18,
    borderRadius: 9,
    padding: '0 6px',
    gap: 3,
    fontSize: '10px',
    fontWeight: 600,
    fontFamily: theme.typography.fontFamily,
    backgroundColor: theme.palette.action.selected,
}));

const SegmentValue = styled('span', {shouldForwardProp: (prop) => prop !== 'variant'})<{variant: NavBadgeVariant}>(
    ({theme, variant}) => ({
        color:
            variant === 'error'
                ? theme.palette.error.main
                : variant === 'warning'
                  ? theme.palette.warning.main
                  : variant === 'info'
                    ? theme.palette.info.main
                    : theme.palette.text.secondary,
        fontWeight: variant === 'error' ? 700 : 600,
    }),
);

const SegmentSeparator = styled('span')(({theme}) => ({color: theme.palette.text.disabled, fontWeight: 400}));

export const NavBadge = ({count, variant = 'default', segments}: NavBadgeProps) => {
    if (segments && segments.length > 0) {
        const visible = segments.filter((s) => s.count > 0);
        if (visible.length === 0) return null;
        return (
            <SegmentedRoot>
                {visible.map((segment, i) => (
                    <span
                        key={`${segment.variant}-${i}`}
                        style={{display: 'inline-flex', alignItems: 'center', gap: 3}}
                    >
                        {i > 0 && <SegmentSeparator>/</SegmentSeparator>}
                        <SegmentValue variant={segment.variant}>{segment.count}</SegmentValue>
                    </span>
                ))}
            </SegmentedRoot>
        );
    }
    if (count === undefined || count === 0 || count === '0') return null;
    return <BadgeRoot variant={variant}>{count}</BadgeRoot>;
};
