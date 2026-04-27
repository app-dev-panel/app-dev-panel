import type {SxProps, Theme} from '@mui/material/styles';

/**
 * Universal UI kit consumed by every SSR collector template.
 *
 * Conventions:
 * - Class names start with `adp-ui-` and mirror an existing MUI component
 *   (chip → Chip, card → Paper, list → List+Divider, details → ButtonBase+Collapse).
 * - Severity-flavoured primitives read a single CSS variable `--adp-sev` set by
 *   `[data-severity="..."]`. One mapping → every chip/pill/dot picks it up.
 * - Backend templates ship structure + class names + `data-*` attributes only.
 *   They never embed colors, palette tokens, or theme-mode awareness.
 *
 * Add new primitives here when a *generic* shape is needed by more than one
 * collector. Collector-specific layout numbers (column widths, indents) belong
 * inline in the template.
 */
export const ssrUiKitSx: SxProps<Theme> = {
    fontFamily: (theme) => theme.typography.fontFamily,
    color: 'text.primary',
    fontSize: 14,
    lineHeight: 1.5,
    '& pre': {m: 0},

    // ----- Typography helpers -------------------------------------------------
    '& .adp-ui-mono': {fontFamily: (theme) => theme.adp?.fontFamilyMono ?? 'monospace'},
    '& .adp-ui-text-secondary': {color: 'text.secondary'},
    '& .adp-ui-text-disabled': {color: 'text.disabled'},
    '& .adp-ui-text-strong': {fontWeight: 600},

    // ----- Layout primitives --------------------------------------------------
    '& .adp-ui-row': {display: 'flex', alignItems: 'flex-start', gap: 1.5},
    '& .adp-ui-row--center': {alignItems: 'center'},
    '& .adp-ui-row--between': {justifyContent: 'space-between'},
    '& .adp-ui-row--wrap': {flexWrap: 'wrap'},
    '& .adp-ui-stack': {display: 'flex', flexDirection: 'column', gap: 1.5},
    '& .adp-ui-fill': {flex: 1, minWidth: 0},

    // ----- Severity colour token (single source of truth) --------------------
    // Set on any element via `data-severity="…"`; downstream primitives read
    // `var(--adp-sev)` so adding a new severity-coloured primitive is one line.
    '& [data-severity="emergency"]': {'--adp-sev': (theme: Theme) => theme.palette.error.main},
    '& [data-severity="alert"]': {'--adp-sev': (theme: Theme) => theme.palette.error.main},
    '& [data-severity="critical"]': {'--adp-sev': (theme: Theme) => theme.palette.error.main},
    '& [data-severity="error"]': {'--adp-sev': (theme: Theme) => theme.palette.error.main},
    '& [data-severity="warning"]': {'--adp-sev': (theme: Theme) => theme.palette.warning.main},
    '& [data-severity="notice"]': {'--adp-sev': (theme: Theme) => theme.palette.primary.main},
    '& [data-severity="info"]': {'--adp-sev': (theme: Theme) => theme.palette.success.main},
    '& [data-severity="debug"]': {'--adp-sev': (theme: Theme) => theme.palette.text.disabled},

    // ----- Chip (mirrors <Chip size="small" variant="outlined">) -------------
    '& .adp-ui-chip': {
        display: 'inline-flex',
        alignItems: 'center',
        height: 24,
        px: 1,
        borderRadius: 1,
        fontSize: 11,
        fontWeight: 600,
        letterSpacing: '0.3px',
        border: '1px solid',
        borderColor: 'divider',
        color: 'text.secondary',
        backgroundColor: 'transparent',
        whiteSpace: 'nowrap',
    },
    '& .adp-ui-chip[data-severity]': {color: 'var(--adp-sev)', borderColor: 'var(--adp-sev)'},
    '& .adp-ui-chip--filled': {bgcolor: 'action.selected', color: 'text.primary', borderColor: 'transparent'},
    '& .adp-ui-chip--filled[data-severity]': {
        bgcolor: 'var(--adp-sev)',
        color: 'common.white',
        borderColor: 'transparent',
    },

    // ----- Badge (small uppercase tag, mirrors a contained <Chip>) -----------
    '& .adp-ui-badge': {
        display: 'inline-flex',
        alignItems: 'center',
        px: 1,
        py: '3px',
        borderRadius: 0.5,
        fontSize: 10,
        fontWeight: 700,
        letterSpacing: '0.4px',
        textTransform: 'uppercase',
        bgcolor: 'primary.main',
        color: 'primary.contrastText',
    },

    // ----- Card (mirrors <Paper variant="outlined">) -------------------------
    '& .adp-ui-card': {
        border: '1px solid',
        borderColor: 'divider',
        borderRadius: 1,
        bgcolor: 'background.paper',
        overflow: 'hidden',
    },
    '& .adp-ui-card--inset': {bgcolor: 'action.hover'},
    '& .adp-ui-card-section': {p: 1.5},

    // ----- Divided list (mirrors <List disablePadding> + <Divider/>) --------
    '& .adp-ui-list > * + *': {borderTop: '1px solid', borderColor: 'divider'},

    // ----- Expandable row (native <details> styled to match MUI) ------------
    '& .adp-ui-details > summary': {
        listStyle: 'none',
        cursor: 'pointer',
        transition: 'background-color .1s ease',
        display: 'flex',
        alignItems: 'flex-start',
        gap: 1.5,
        px: 1.5,
        py: 1,
    },
    '& .adp-ui-details > summary::-webkit-details-marker': {display: 'none'},
    '& .adp-ui-details > summary:hover': {bgcolor: 'action.hover'},
    '& .adp-ui-details[open] > summary': {bgcolor: 'action.hover'},
    '& .adp-ui-caret': {flexShrink: 0, color: 'text.disabled', transition: 'transform .15s ease'},
    '& .adp-ui-details[open] .adp-ui-caret': {transform: 'rotate(180deg)'},

    // ----- Code block (mirrors what JsonRenderer/CodeHighlight render) ------
    '& .adp-ui-code': {
        bgcolor: (theme) => (theme.palette.mode === 'dark' ? theme.palette.background.default : '#1e1e2e'),
        color: (theme) => (theme.palette.mode === 'dark' ? theme.palette.text.primary : '#cdd6f4'),
        p: 1.5,
        borderRadius: 0.75,
        fontFamily: (theme) => theme.adp?.fontFamilyMono ?? 'monospace',
        fontSize: 12,
        lineHeight: 1.5,
        overflowX: 'auto',
        whiteSpace: 'pre-wrap',
        wordBreak: 'break-word',
    },

    // ----- Empty state -------------------------------------------------------
    '& .adp-ui-empty': {
        p: 4,
        textAlign: 'center',
        color: 'text.secondary',
        fontSize: 14,
        border: '1px dashed',
        borderColor: 'divider',
        borderRadius: 1,
    },
};
