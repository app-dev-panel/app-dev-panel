import {describe, expect, it} from 'vitest';
import {createAdpTheme} from './DefaultTheme';
import {darkSemanticTokens, semanticTokens} from './tokens';

describe('createAdpTheme', () => {
    const theme = createAdpTheme('light', {openLinksInNewWindow: false, baseUrl: ''});

    it('creates a valid MUI theme', () => {
        expect(theme).toBeDefined();
        expect(theme.palette).toBeDefined();
        expect(theme.typography).toBeDefined();
    });

    it('applies semantic palette', () => {
        expect(theme.palette.primary.main).toBe(semanticTokens.palette.primary.main);
        expect(theme.palette.success.main).toBe(semanticTokens.palette.success.main);
        expect(theme.palette.error.main).toBe(semanticTokens.palette.error.main);
    });

    it('applies semantic typography', () => {
        expect(theme.typography.fontFamily).toBe(semanticTokens.typography.fontFamily);
    });

    it('applies semantic shape', () => {
        expect(theme.shape.borderRadius).toBe(semanticTokens.shape.borderRadius);
    });

    it('creates dark mode theme with dark tokens', () => {
        const darkTheme = createAdpTheme('dark', {openLinksInNewWindow: false, baseUrl: ''});
        expect(darkTheme.palette.mode).toBe('dark');
        expect(darkTheme.palette.background.default).toBe(darkSemanticTokens.palette.background.default);
        expect(darkTheme.palette.background.paper).toBe(darkSemanticTokens.palette.background.paper);
    });

    it('disables button text transform', () => {
        expect(theme.components?.MuiButton?.styleOverrides?.root).toMatchObject({textTransform: 'none'});
    });

    it('exposes adp.fontFamilyMono on theme', () => {
        expect(theme.adp.fontFamilyMono).toBe(semanticTokens.typography.fontFamilyMono);
    });

    it('exposes light chart colors on light theme', () => {
        expect(theme.adp.chartColors).toEqual(semanticTokens.chartColors);
    });

    it('exposes dark chart colors on dark theme', () => {
        const darkTheme = createAdpTheme('dark', {openLinksInNewWindow: false, baseUrl: ''});
        expect(darkTheme.adp.chartColors).toEqual(darkSemanticTokens.chartColors);
    });

    it('exposes light collector colors on light theme', () => {
        expect(theme.adp.collectorColors.request).toEqual(semanticTokens.collectorColors.request);
    });

    it('exposes dark collector colors on dark theme', () => {
        const darkTheme = createAdpTheme('dark', {openLinksInNewWindow: false, baseUrl: ''});
        expect(darkTheme.adp.collectorColors.request).toEqual(darkSemanticTokens.collectorColors.request);
    });

    it('exposes highlight color', () => {
        expect(theme.adp.highlightColor).toBe(semanticTokens.highlightColor);
    });

    it('includes micro typography variant', () => {
        expect(theme.typography.micro).toMatchObject({fontSize: '10px', fontWeight: 600});
    });
});
