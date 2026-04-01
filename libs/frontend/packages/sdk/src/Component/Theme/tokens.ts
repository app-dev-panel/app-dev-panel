/**
 * ADP Design Tokens — three-layer architecture.
 *
 * 1. Primitive tokens — raw values, never used directly in components.
 * 2. Semantic tokens — mapped to meaning, consumed by the MUI theme.
 * 3. Component tokens — applied via theme styleOverrides / sx.
 */

// ---------------------------------------------------------------------------
// 1. Primitive tokens
// ---------------------------------------------------------------------------

export const primitives = {
    // Blues
    blue50: '#EFF6FF',
    blue500: '#2563EB',
    blue700: '#1D4ED8',

    // Grays
    gray50: '#F3F4F6',
    gray100: '#F5F5F5',
    gray200: '#E5E5E5',
    gray300: '#F0F0F0',
    gray400: '#999999',
    gray600: '#666666',
    gray900: '#1A1A1A',

    // Status
    green600: '#16A34A',
    amber600: '#D97706',
    red50: '#FEE2E2',
    red600: '#DC2626',

    // Surfaces
    white: '#FFFFFF',

    // Typography
    fontFamily: "'Inter', sans-serif",
    fontFamilyMono: "'JetBrains Mono', monospace",

    // Spacing base unit (px)
    spaceUnit: 8,

    // Border radius base (px)
    radiusBase: 8,
} as const;

// ---------------------------------------------------------------------------
// 2. Semantic tokens (consumed by createAdpTheme)
// ---------------------------------------------------------------------------

export const semanticTokens = {
    palette: {
        primary: {main: primitives.blue500, light: primitives.blue50, dark: primitives.blue700},
        success: {main: primitives.green600, light: '#DCFCE7'},
        warning: {main: primitives.amber600, light: '#FEF3C7'},
        error: {main: primitives.red600, light: primitives.red50},
        background: {default: primitives.gray50, paper: primitives.white},
        text: {primary: primitives.gray900, secondary: primitives.gray600, disabled: primitives.gray400},
        divider: primitives.gray200,
    },
    typography: {
        fontFamily: primitives.fontFamily,
        fontFamilyMono: primitives.fontFamilyMono,
        h4: {fontSize: '18px', fontWeight: 600, lineHeight: 1.4},
        body1: {fontSize: '14px', fontWeight: 400, lineHeight: 1.5},
        body2: {fontSize: '13px', fontWeight: 400, lineHeight: 1.5},
        caption: {fontSize: '11px', fontWeight: 600, lineHeight: 1.3},
        micro: {fontSize: '10px', fontWeight: 600, lineHeight: 1.3},
        overline: {
            fontSize: '12px',
            fontWeight: 600,
            letterSpacing: '0.6px',
            textTransform: 'uppercase' as const,
            lineHeight: 1.5,
        },
    },
    shape: {borderRadius: primitives.radiusBase},
    shadows: {
        sm: '0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04)',
        md: '0 4px 12px rgba(0,0,0,0.08)',
        lg: '0 8px 24px rgba(0,0,0,0.12), 0 2px 6px rgba(0,0,0,0.08)',
    },
    chartColors: [
        '#42A5F5', // blue
        '#AB47BC', // purple
        '#66BB6A', // green
        '#FFA726', // orange
        '#26C6DA', // cyan
        '#EC407A', // pink
        '#8D6E63', // brown
        '#78909C', // blue-gray
        '#FFCA28', // yellow (adjusted from #FFEE58 for better contrast)
        '#7C3AED', // violet
    ],
    collectorColors: {
        request: {bg: '#EFF6FF', fg: '#2563EB'},
        log: {bg: '#FEF3C7', fg: '#D97706'},
        event: {bg: '#F3E8FF', fg: '#9333EA'},
        database: {bg: '#ECFDF5', fg: '#16A34A'},
        middleware: {bg: '#FFF7ED', fg: '#EA580C'},
        exception: {bg: '#FEF2F2', fg: '#DC2626'},
        service: {bg: '#F0F9FF', fg: '#0284C7'},
        timeline: {bg: '#F5F3FF', fg: '#7C3AED'},
        varDumper: {bg: '#F5F5F5', fg: '#666666'},
        mailer: {bg: '#FDF4FF', fg: '#A855F7'},
        filesystem: {bg: '#FFF7ED', fg: '#EA580C'},
        cache: {bg: '#ECFDF5', fg: '#059669'},
        template: {bg: '#FEF3C7', fg: '#B45309'},
        authorization: {bg: '#FEF2F2', fg: '#DC2626'},
        deprecation: {bg: '#FFF3E0', fg: '#E65100'},
        environment: {bg: '#E8F5E9', fg: '#2E7D32'},
        translator: {bg: '#E3F2FD', fg: '#1565C0'},
        default: {bg: '#F5F5F5', fg: '#666666'},
    },
    highlightColor: '#ffcccc',
} as const;

// ---------------------------------------------------------------------------
// 2b. Dark-mode semantic overrides
// ---------------------------------------------------------------------------

export const darkSemanticTokens = {
    palette: {
        primary: {main: '#60A5FA', light: '#1E3A5F', dark: '#3B82F6'},
        success: {main: '#4ADE80', light: '#14532D'},
        warning: {main: '#FBBF24', light: '#713F12'},
        error: {main: '#F87171', light: '#7F1D1D'},
        background: {default: '#0F172A', paper: '#1E293B'},
        text: {primary: '#F1F5F9', secondary: '#94A3B8', disabled: '#64748B'},
        divider: '#334155',
    },
    chartColors: [
        '#64B5F6', // blue (brighter)
        '#CE93D8', // purple (brighter)
        '#81C784', // green (brighter)
        '#FFB74D', // orange (brighter)
        '#4DD0E1', // cyan (brighter)
        '#F06292', // pink (brighter)
        '#A1887F', // brown (brighter)
        '#90A4AE', // blue-gray (brighter)
        '#FFD54F', // yellow (brighter)
        '#B39DDB', // violet (brighter)
    ],
    collectorColors: {
        request: {bg: '#1E3A5F', fg: '#60A5FA'},
        log: {bg: '#713F12', fg: '#FBBF24'},
        event: {bg: '#3B1F6E', fg: '#C084FC'},
        database: {bg: '#14532D', fg: '#4ADE80'},
        middleware: {bg: '#7C2D12', fg: '#FB923C'},
        exception: {bg: '#7F1D1D', fg: '#F87171'},
        service: {bg: '#0C4A6E', fg: '#38BDF8'},
        timeline: {bg: '#2E1065', fg: '#A78BFA'},
        varDumper: {bg: '#334155', fg: '#94A3B8'},
        mailer: {bg: '#4A044E', fg: '#D946EF'},
        filesystem: {bg: '#7C2D12', fg: '#FB923C'},
        cache: {bg: '#14532D', fg: '#34D399'},
        template: {bg: '#713F12', fg: '#F59E0B'},
        authorization: {bg: '#7F1D1D', fg: '#F87171'},
        deprecation: {bg: '#7C2D12', fg: '#FB923C'},
        environment: {bg: '#14532D', fg: '#4ADE80'},
        translator: {bg: '#1E3A5F', fg: '#60A5FA'},
        default: {bg: '#334155', fg: '#94A3B8'},
    },
    highlightColor: '#5C2020',
} as const;

// ---------------------------------------------------------------------------
// 3. Component tokens
// ---------------------------------------------------------------------------

export const componentTokens = {
    topBar: {height: 48},
    sidebar: {width: 200, borderRadius: primitives.radiusBase * 2},
    contentPanel: {borderRadius: primitives.radiusBase * 2},
    navItem: {height: 38, borderRadius: primitives.radiusBase, activeBarWidth: 3},
    mainGap: 16,
    mainMaxWidth: 1160,
} as const;
