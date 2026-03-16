import {describe, expect, it} from 'vitest';
import {componentTokens, primitives, semanticTokens} from './tokens';

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

    it('has correct h4 size', () => {
        expect(semanticTokens.typography.h4.fontSize).toBe('18px');
    });

    it('has 8px border radius', () => {
        expect(semanticTokens.shape.borderRadius).toBe(8);
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
