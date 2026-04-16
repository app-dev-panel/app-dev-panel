import {Chip} from '@mui/material';
import {alpha, styled} from '@mui/material/styles';

/**
 * FilterChip — unified colored filter/tag badge.
 *
 * Fixes the MUI default gray `action.hover` that clashes with a colored outline.
 * When `color` is provided:
 *   - inactive state: transparent background, colored border + text;
 *     on hover/focus — a subtle tint of the same color instead of gray.
 *   - active state: filled with the color + white text;
 *     on hover/focus — slightly translucent to signal interactivity.
 * When `color` is omitted:
 *   - behaves as a neutral outlined chip (e.g. "Clear").
 *
 * Replaces the inline `sx` filter-chip pattern that existed across
 * TimelinePanel, ElasticsearchPanel, HttpClientPanel, ComposerPage, RoutesPage.
 */
export type FilterChipProps = {
    label: string;
    count?: number;
    /** Outline + text color (and active fill). Omit for the neutral "Clear" look. */
    color?: string;
    /** Whether the filter is currently active. Active chips are filled. */
    active?: boolean;
    onClick?: () => void;
};

type StyledChipProps = {chipColor?: string; active?: boolean};

const StyledChip = styled(Chip, {
    shouldForwardProp: (prop) => prop !== 'chipColor' && prop !== 'active',
})<StyledChipProps>(({theme, chipColor, active}) => {
    const accent = chipColor ?? theme.palette.text.secondary;
    const borderColor = chipColor ?? theme.palette.divider;
    const isActive = Boolean(active);
    return {
        fontSize: '11px',
        height: 24,
        borderRadius: theme.shape.borderRadius,
        fontWeight: 600,
        border: `1px solid ${borderColor}`,
        backgroundColor: isActive ? accent : 'transparent',
        color: isActive ? theme.palette.common.white : accent,
        transition: theme.transitions.create(['background-color', 'color', 'box-shadow'], {
            duration: theme.transitions.duration.shortest,
        }),
        '&.MuiChip-clickable:hover, &.MuiChip-clickable:focus-visible': {
            backgroundColor: isActive
                ? alpha(accent, 0.85)
                : chipColor
                  ? alpha(chipColor, 0.12)
                  : theme.palette.action.hover,
            color: isActive ? theme.palette.common.white : accent,
            boxShadow: chipColor ? `0 0 0 1px ${alpha(chipColor, 0.35)}` : 'none',
        },
        '& .MuiChip-label': {paddingLeft: theme.spacing(1), paddingRight: theme.spacing(1)},
    };
});

export const FilterChip = ({label, count, color, active = false, onClick}: FilterChipProps) => {
    const displayLabel = count !== undefined ? `${label} (${count})` : label;
    return (
        <StyledChip
            label={displayLabel}
            size="small"
            onClick={onClick}
            clickable={Boolean(onClick)}
            chipColor={color}
            active={active}
        />
    );
};
