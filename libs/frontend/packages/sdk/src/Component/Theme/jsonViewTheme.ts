/**
 * Custom @uiw/react-json-view themes derived from ADP design tokens.
 *
 * Uses CSS custom properties consumed by the JsonView component.
 * Transparent background so the viewer inherits from its container.
 */

import {darkSemanticTokens, primitives, semanticTokens} from './tokens';

export const jsonViewLightTheme: React.CSSProperties = {
    '--w-rjv-font-family': primitives.fontFamilyMono,
    '--w-rjv-color': semanticTokens.palette.text.primary,
    '--w-rjv-background-color': 'transparent',
    '--w-rjv-line-color': semanticTokens.palette.divider,
    '--w-rjv-arrow-color': semanticTokens.palette.text.disabled,
    '--w-rjv-info-color': semanticTokens.palette.text.disabled,
    '--w-rjv-update-color': semanticTokens.palette.primary.main,
    '--w-rjv-copied-color': semanticTokens.palette.text.secondary,
    '--w-rjv-copied-success-color': semanticTokens.palette.success.main,
    '--w-rjv-curlybraces-color': semanticTokens.palette.text.disabled,
    '--w-rjv-colon-color': semanticTokens.palette.text.disabled,
    '--w-rjv-brackets-color': semanticTokens.palette.text.disabled,
    '--w-rjv-ellipsis-color': semanticTokens.palette.primary.main,
    '--w-rjv-quotes-color': 'transparent',
    '--w-rjv-quotes-string-color': 'transparent',
    '--w-rjv-type-string-color': '#B35309',
    '--w-rjv-type-int-color': semanticTokens.palette.primary.main,
    '--w-rjv-type-float-color': semanticTokens.palette.primary.main,
    '--w-rjv-type-bigint-color': semanticTokens.palette.primary.main,
    '--w-rjv-type-boolean-color': '#7C3AED',
    '--w-rjv-type-date-color': semanticTokens.palette.warning.main,
    '--w-rjv-type-url-color': semanticTokens.palette.primary.main,
    '--w-rjv-type-null-color': semanticTokens.palette.text.disabled,
    '--w-rjv-type-nan-color': semanticTokens.palette.error.main,
    '--w-rjv-type-undefined-color': semanticTokens.palette.text.disabled,
    '--w-rjv-key-number': semanticTokens.palette.text.secondary,
    '--w-rjv-key-string': semanticTokens.palette.text.secondary,
    '--w-rjv-border-left-width': 1,
} as React.CSSProperties;

export const jsonViewDarkTheme: React.CSSProperties = {
    '--w-rjv-font-family': primitives.fontFamilyMono,
    '--w-rjv-color': darkSemanticTokens.palette.text.primary,
    '--w-rjv-background-color': 'transparent',
    '--w-rjv-line-color': darkSemanticTokens.palette.divider,
    '--w-rjv-arrow-color': darkSemanticTokens.palette.text.disabled,
    '--w-rjv-info-color': darkSemanticTokens.palette.text.disabled,
    '--w-rjv-update-color': darkSemanticTokens.palette.primary.main,
    '--w-rjv-copied-color': darkSemanticTokens.palette.text.secondary,
    '--w-rjv-copied-success-color': darkSemanticTokens.palette.success.main,
    '--w-rjv-curlybraces-color': darkSemanticTokens.palette.text.disabled,
    '--w-rjv-colon-color': darkSemanticTokens.palette.text.disabled,
    '--w-rjv-brackets-color': darkSemanticTokens.palette.text.disabled,
    '--w-rjv-ellipsis-color': darkSemanticTokens.palette.primary.main,
    '--w-rjv-quotes-color': 'transparent',
    '--w-rjv-quotes-string-color': 'transparent',
    '--w-rjv-type-string-color': '#FBBF24',
    '--w-rjv-type-int-color': darkSemanticTokens.palette.primary.main,
    '--w-rjv-type-float-color': darkSemanticTokens.palette.primary.main,
    '--w-rjv-type-bigint-color': darkSemanticTokens.palette.primary.main,
    '--w-rjv-type-boolean-color': '#C084FC',
    '--w-rjv-type-date-color': darkSemanticTokens.palette.warning.main,
    '--w-rjv-type-url-color': darkSemanticTokens.palette.primary.main,
    '--w-rjv-type-null-color': darkSemanticTokens.palette.text.disabled,
    '--w-rjv-type-nan-color': darkSemanticTokens.palette.error.main,
    '--w-rjv-type-undefined-color': darkSemanticTokens.palette.text.disabled,
    '--w-rjv-key-number': darkSemanticTokens.palette.text.secondary,
    '--w-rjv-key-string': darkSemanticTokens.palette.text.secondary,
    '--w-rjv-border-left-width': 1,
} as React.CSSProperties;
