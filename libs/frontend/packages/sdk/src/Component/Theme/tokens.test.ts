import {describe, expect, it} from 'vitest';
import {componentTokens, darkSemanticTokens, primitives, semanticTokens} from './tokens';

describe('primitives', () => {
    it('has correct primary blue', () => {
        expect(primitives.blue500).toBe('#2563EB');
    });

    it('has correct error red', () => {
        expect(primitives.red600).toBe('#DC2626');
    });

    it('has Inter font family', () => {
        expect(primitives.fontFamily).toContain('Inter');
    });

    it('has JetBrains Mono for code', () => {
        expect(primitives.fontFamilyMono).toContain('JetBrains Mono');
    });

    it('has 8px spacing unit', () => {
        expect(primitives.spaceUnit).toBe(8);
    });

    it('has 8px radius base', () => {
        expect(primitives.radiusBase).toBe(8);
    });
});

describe('semanticTokens', () => {
    it('maps primary to blue500', () => {
        expect(semanticTokens.palette.primary.main).toBe(primitives.blue500);
    });

    it('maps background.default to gray50', () => {
        expect(semanticTokens.palette.background.default).toBe(primitives.gray50);
    });

    it('has correct typography fontFamily', () => {
        expect(semanticTokens.typography.fontFamily).toBe(primitives.fontFamily);
    });

    it('has fontFamilyMono mapped from primitives', () => {
        expect(semanticTokens.typography.fontFamilyMono).toBe(primitives.fontFamilyMono);
    });

    it('has correct h4 size', () => {
        expect(semanticTokens.typography.h4.fontSize).toBe('18px');
    });

    it('has micro typography variant at 10px', () => {
        expect(semanticTokens.typography.micro.fontSize).toBe('10px');
        expect(semanticTokens.typography.micro.fontWeight).toBe(600);
    });

    it('has 8px border radius', () => {
        expect(semanticTokens.shape.borderRadius).toBe(8);
    });

    it('has 10 chart colors', () => {
        expect(semanticTokens.chartColors).toHaveLength(10);
    });

    it('has collector colors with bg/fg pairs', () => {
        expect(semanticTokens.collectorColors.request).toEqual({bg: '#EFF6FF', fg: '#2563EB'});
        expect(semanticTokens.collectorColors.default).toEqual({bg: '#F5F5F5', fg: '#666666'});
    });

    it('has highlight color', () => {
        expect(semanticTokens.highlightColor).toBe('#ffcccc');
    });
});

describe('darkSemanticTokens', () => {
    it('has dark background colors', () => {
        expect(darkSemanticTokens.palette.background.default).toBe('#0F172A');
        expect(darkSemanticTokens.palette.background.paper).toBe('#1E293B');
    });

    it('has light text for dark mode', () => {
        expect(darkSemanticTokens.palette.text.primary).toBe('#F1F5F9');
    });

    it('has dark divider color', () => {
        expect(darkSemanticTokens.palette.divider).toBe('#334155');
    });

    it('has 10 dark chart colors', () => {
        expect(darkSemanticTokens.chartColors).toHaveLength(10);
    });

    it('has dark collector colors with bg/fg pairs', () => {
        expect(darkSemanticTokens.collectorColors.request).toEqual({bg: '#1E3A5F', fg: '#60A5FA'});
        expect(darkSemanticTokens.collectorColors.default).toEqual({bg: '#334155', fg: '#94A3B8'});
    });

    it('has dark highlight color', () => {
        expect(darkSemanticTokens.highlightColor).toBe('#5C2020');
    });
});

describe('componentTokens', () => {
    it('has correct topBar height', () => {
        expect(componentTokens.topBar.height).toBe(48);
    });

    it('has correct sidebar width', () => {
        expect(componentTokens.sidebar.width).toBe(200);
    });

    it('has correct sidebar border radius (2x base)', () => {
        expect(componentTokens.sidebar.borderRadius).toBe(16);
    });

    it('has correct content panel border radius (2x base)', () => {
        expect(componentTokens.contentPanel.borderRadius).toBe(16);
    });

    it('has correct nav item height', () => {
        expect(componentTokens.navItem.height).toBe(38);
    });

    it('has correct main gap', () => {
        expect(componentTokens.mainGap).toBe(16);
    });

    it('has correct max width', () => {
        expect(componentTokens.mainMaxWidth).toBe(1160);
    });
});
