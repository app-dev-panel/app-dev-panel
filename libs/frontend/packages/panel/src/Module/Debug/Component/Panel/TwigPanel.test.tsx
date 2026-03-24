import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it} from 'vitest';
import {TwigPanel} from './TwigPanel';

const makeRender = (overrides: Partial<{template: string; renderTime: number}> = {}) => ({
    template: 'home/index.html.twig',
    renderTime: 5.2,
    ...overrides,
});

const makeData = (overrides: Partial<{renders: any[]; totalTime: number; renderCount: number}> = {}) => ({
    renders: [makeRender()],
    totalTime: 5.2,
    renderCount: 1,
    ...overrides,
});

describe('TwigPanel', () => {
    it('shows empty message when no data', () => {
        renderWithProviders(<TwigPanel data={null as any} />);
        expect(screen.getByText(/No Twig renders found/)).toBeInTheDocument();
    });

    it('shows empty message when renders is empty', () => {
        renderWithProviders(<TwigPanel data={makeData({renders: []})} />);
        expect(screen.getByText(/No Twig renders found/)).toBeInTheDocument();
    });

    it('renders summary cards', () => {
        renderWithProviders(<TwigPanel data={makeData({renderCount: 5})} />);
        expect(screen.getByText('Templates Rendered')).toBeInTheDocument();
        expect(screen.getByText('5')).toBeInTheDocument();
        expect(screen.getByText('Total Render Time')).toBeInTheDocument();
    });

    it('renders count in section title', () => {
        renderWithProviders(<TwigPanel data={makeData()} />);
        expect(screen.getByText('1 renders')).toBeInTheDocument();
    });

    it('renders template name', () => {
        renderWithProviders(<TwigPanel data={makeData({renders: [makeRender({template: 'layout.html.twig'})]})} />);
        expect(screen.getByText('layout.html.twig')).toBeInTheDocument();
    });

    it('renders multiple templates', () => {
        const renders = [makeRender({template: 'base.html.twig'}), makeRender({template: 'header.html.twig'})];
        renderWithProviders(<TwigPanel data={makeData({renders, renderCount: 2})} />);
        expect(screen.getByText('2 renders')).toBeInTheDocument();
        expect(screen.getByText('base.html.twig')).toBeInTheDocument();
        expect(screen.getByText('header.html.twig')).toBeInTheDocument();
    });

    it('filters templates by name', async () => {
        const user = userEvent.setup();
        const renders = [makeRender({template: 'base.html.twig'}), makeRender({template: 'header.html.twig'})];
        renderWithProviders(<TwigPanel data={makeData({renders, renderCount: 2})} />);
        await user.type(screen.getByPlaceholderText('Filter templates...'), 'header');
        expect(screen.getByText('header.html.twig')).toBeInTheDocument();
        expect(screen.queryByText('base.html.twig')).not.toBeInTheDocument();
    });
});
