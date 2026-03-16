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
        success: {main: primitives.green600},
        warning: {main: primitives.amber600},
        error: {main: primitives.red600, light: primitives.red50},
        background: {default: primitives.gray50, paper: primitives.white},
        text: {primary: primitives.gray900, secondary: primitives.gray600, disabled: primitives.gray400},
        divider: primitives.gray200,
    },
    typography: {
        fontFamily: primitives.fontFamily,
        h4: {fontSize: '18px', fontWeight: 600, lineHeight: 1.4},
        body1: {fontSize: '14px', fontWeight: 400, lineHeight: 1.5},
        body2: {fontSize: '13px', fontWeight: 400, lineHeight: 1.5},
        caption: {fontSize: '11px', fontWeight: 600, lineHeight: 1.3},
        overline: {
            fontSize: '12px',
            fontWeight: 600,
            letterSpacing: '0.6px',
            textTransform: 'uppercase' as const,
            lineHeight: 1.5,
        },
    },
    shape: {borderRadius: primitives.radiusBase},
    shadows: {sm: '0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04)', md: '0 4px 12px rgba(0,0,0,0.08)'},
} as const;

// ---------------------------------------------------------------------------
// 2b. Dark-mode semantic overrides
// ---------------------------------------------------------------------------

export const darkSemanticTokens = {
    palette: {
        primary: {main: '#60A5FA', light: '#1E3A5F', dark: '#3B82F6'},
        success: {main: '#4ADE80'},
        warning: {main: '#FBBF24'},
        error: {main: '#F87171', light: '#7F1D1D'},
        background: {default: '#0F172A', paper: '#1E293B'},
        text: {primary: '#F1F5F9', secondary: '#94A3B8', disabled: '#64748B'},
        divider: '#334155',
    },
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
