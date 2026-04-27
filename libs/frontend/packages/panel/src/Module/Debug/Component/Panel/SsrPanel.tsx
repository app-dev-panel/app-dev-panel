import {Box} from '@mui/material';
import type {SxProps, Theme} from '@mui/material/styles';

type SsrPanelProps = {html: string};

/**
 * Renders a server-rendered HTML fragment produced by a backend collector that
 * implements `HtmlViewProviderInterface` (data shape `{__html: "<...>"}`).
 *
 * The backend ships **structure only** — semantic class names and `data-*`
 * attributes. All visual styling lives here so the panel inherits the theme
 * (light/dark, palette, typography, spacing) and the backend doesn't need to
 * know anything about colors. New SSR collector types should reuse the
 * `.adp-ssr-panel` wrapper for shared base styles and add their own
 * BEM-style sub-classes (e.g. `.adp-ssr-logs__row`).
 */
export const SsrPanel = ({html}: SsrPanelProps) => (
    <Box sx={ssrPanelSx} dangerouslySetInnerHTML={{__html: html}} />
);

// -----------------------------------------------------------------------------
// All SSR panel styles live here — base shell + per-template selectors keyed
// off semantic class names that the backend ships. Severity colors come from
// the MUI palette via `data-level` so the backend never embeds colors.
// -----------------------------------------------------------------------------

const ssrPanelSx: SxProps<Theme> = {
    p: 2,
    // Shared shell for any SSR panel template
    '& .adp-ssr-panel': {fontFamily: (theme) => theme.typography.fontFamily, color: 'text.primary', fontSize: 14},
    '& .adp-ssr-panel pre': {margin: 0},
    // SsrLogPanelCollector template
    '& .adp-ssr-logs__header': {
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'space-between',
        gap: 1.5,
        flexWrap: 'wrap',
        mb: 1.5,
    },
    '& .adp-ssr-logs__title': {fontSize: 13, fontWeight: 600, color: 'text.secondary', m: 0},
    '& .adp-ssr-logs__badge': {
        fontSize: 10,
        fontWeight: 700,
        letterSpacing: '0.4px',
        textTransform: 'uppercase',
        bgcolor: 'primary.main',
        color: 'primary.contrastText',
        px: 1,
        py: '3px',
        borderRadius: 0.5,
    },
    '& .adp-ssr-logs__chips': {display: 'flex', flexWrap: 'wrap', gap: 0.75, mb: 1.5},
    '& .adp-ssr-logs__chip': {
        display: 'inline-flex',
        alignItems: 'center',
        height: 22,
        px: 1.25,
        borderRadius: 0.5,
        fontSize: 10,
        fontWeight: 700,
        letterSpacing: '0.4px',
        textTransform: 'uppercase',
        border: '1px solid',
        borderColor: 'divider',
        color: 'text.secondary',
    },
    '& .adp-ssr-logs__chip[data-level="emergency"]': {color: 'error.main', borderColor: 'error.main'},
    '& .adp-ssr-logs__chip[data-level="alert"]': {color: 'error.main', borderColor: 'error.main'},
    '& .adp-ssr-logs__chip[data-level="critical"]': {color: 'error.main', borderColor: 'error.main'},
    '& .adp-ssr-logs__chip[data-level="error"]': {color: 'error.main', borderColor: 'error.main'},
    '& .adp-ssr-logs__chip[data-level="warning"]': {color: 'warning.main', borderColor: 'warning.main'},
    '& .adp-ssr-logs__chip[data-level="notice"]': {color: 'primary.main', borderColor: 'primary.main'},
    '& .adp-ssr-logs__chip[data-level="info"]': {color: 'success.main', borderColor: 'success.main'},
    '& .adp-ssr-logs__chip[data-level="debug"]': {color: 'text.disabled', borderColor: 'divider'},

    '& .adp-ssr-logs__list': {
        border: '1px solid',
        borderColor: 'divider',
        borderRadius: 1,
        overflow: 'hidden',
        bgcolor: 'background.paper',
    },
    '& .adp-ssr-logs__row': {borderBottom: '1px solid', borderColor: 'divider'},
    '& .adp-ssr-logs__row:last-child': {borderBottom: 'none'},
    '& .adp-ssr-logs__summary': {
        display: 'flex',
        alignItems: 'flex-start',
        gap: 1.5,
        px: 1.5,
        py: 1,
        cursor: 'pointer',
        listStyle: 'none',
        transition: 'background-color 0.1s ease',
    },
    '& .adp-ssr-logs__summary::-webkit-details-marker': {display: 'none'},
    '& .adp-ssr-logs__summary:hover, & .adp-ssr-logs__row[open] > .adp-ssr-logs__summary': {bgcolor: 'action.hover'},
    '& .adp-ssr-logs__time': {
        fontFamily: (theme) => theme.adp?.fontFamilyMono ?? 'monospace',
        fontSize: 11,
        flexShrink: 0,
        width: 110,
        pt: '2px',
        color: 'text.disabled',
    },
    '& .adp-ssr-logs__level': {
        display: 'inline-flex',
        alignItems: 'center',
        justifyContent: 'center',
        minWidth: 64,
        height: 20,
        px: 1,
        borderRadius: 0.5,
        fontSize: 10,
        fontWeight: 700,
        letterSpacing: '0.4px',
        color: 'common.white',
        flexShrink: 0,
        mt: '1px',
        bgcolor: 'text.disabled',
    },
    '& .adp-ssr-logs__level[data-level="emergency"]': {bgcolor: 'error.main'},
    '& .adp-ssr-logs__level[data-level="alert"]': {bgcolor: 'error.main'},
    '& .adp-ssr-logs__level[data-level="critical"]': {bgcolor: 'error.main'},
    '& .adp-ssr-logs__level[data-level="error"]': {bgcolor: 'error.main'},
    '& .adp-ssr-logs__level[data-level="warning"]': {bgcolor: 'warning.main'},
    '& .adp-ssr-logs__level[data-level="notice"]': {bgcolor: 'primary.main'},
    '& .adp-ssr-logs__level[data-level="info"]': {bgcolor: 'success.main'},
    '& .adp-ssr-logs__level[data-level="debug"]': {bgcolor: 'text.disabled'},

    '& .adp-ssr-logs__message': {
        flex: 1,
        fontSize: 13,
        wordBreak: 'break-word',
        lineHeight: 1.5,
        color: 'text.primary',
    },
    '& .adp-ssr-logs__caret': {
        flexShrink: 0,
        color: 'text.disabled',
        fontSize: 12,
        pt: '3px',
        transition: 'transform 0.15s ease',
    },
    '& .adp-ssr-logs__row[open] .adp-ssr-logs__caret': {transform: 'rotate(180deg)'},
    '& .adp-ssr-logs__detail': {
        pt: 1.5,
        pb: 2,
        pl: '134px',
        pr: 1.5,
        bgcolor: 'action.hover',
        borderTop: '1px solid',
        borderColor: 'divider',
        fontSize: 12,
    },
    '& .adp-ssr-logs__detail-line': {
        fontFamily: (theme) => theme.adp?.fontFamilyMono ?? 'monospace',
        color: 'primary.main',
        mb: 1,
        wordBreak: 'break-all',
    },
    '& .adp-ssr-logs__detail-context': {
        bgcolor: (theme) => (theme.palette.mode === 'dark' ? 'background.default' : '#1e1e2e'),
        color: (theme) => (theme.palette.mode === 'dark' ? 'text.primary' : '#cdd6f4'),
        p: 1.5,
        borderRadius: 0.75,
        fontFamily: (theme) => theme.adp?.fontFamilyMono ?? 'monospace',
        fontSize: 12,
        lineHeight: 1.5,
        overflowX: 'auto',
        whiteSpace: 'pre-wrap',
        wordBreak: 'break-word',
    },
    '& .adp-ssr-logs__empty': {
        p: 4,
        textAlign: 'center',
        color: 'text.secondary',
        fontSize: 14,
        border: '1px dashed',
        borderColor: 'divider',
        borderRadius: 1,
    },
};
