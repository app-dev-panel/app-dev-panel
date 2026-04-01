import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it} from 'vitest';
import {TemplatePanel} from './TemplatePanel';

const makeRender = (
    overrides: Partial<{template: string; renderTime: number; output: string; parameters: any[]}> = {},
) => ({template: 'home/index.html.twig', renderTime: 5.2, output: '', parameters: [], ...overrides});

const makeData = (
    overrides: Partial<{
        renders: any[];
        totalTime: number;
        renderCount: number;
        duplicates: {groups: any[]; totalDuplicatedCount: number};
    }> = {},
) => ({
    renders: [makeRender()],
    totalTime: 5.2,
    renderCount: 1,
    duplicates: {groups: [], totalDuplicatedCount: 0},
    ...overrides,
});

describe('TemplatePanel', () => {
    it('shows empty message when no data', () => {
        renderWithProviders(<TemplatePanel data={null} />);
        expect(screen.getByText(/No template renders found/)).toBeInTheDocument();
    });

    it('shows empty message when renders is empty', () => {
        renderWithProviders(<TemplatePanel data={makeData({renders: []})} />);
        expect(screen.getByText(/No template renders found/)).toBeInTheDocument();
    });

    it('renders summary cards with count', () => {
        renderWithProviders(<TemplatePanel data={makeData({renderCount: 5})} />);
        expect(screen.getByText('Templates Rendered')).toBeInTheDocument();
        expect(screen.getByText('5')).toBeInTheDocument();
    });

    it('renders total render time when timing present', () => {
        renderWithProviders(<TemplatePanel data={makeData()} />);
        expect(screen.getByText('Total Render Time')).toBeInTheDocument();
    });

    it('hides total render time when no timing', () => {
        renderWithProviders(<TemplatePanel data={makeData({renders: [makeRender({renderTime: 0})], totalTime: 0})} />);
        expect(screen.queryByText('Total Render Time')).not.toBeInTheDocument();
    });

    it('renders count in section title', () => {
        renderWithProviders(<TemplatePanel data={makeData()} />);
        expect(screen.getByText('1 renders')).toBeInTheDocument();
    });

    it('renders template name', () => {
        renderWithProviders(<TemplatePanel data={makeData({renders: [makeRender({template: 'layout.html.twig'})]})} />);
        expect(screen.getByText('layout.html.twig')).toBeInTheDocument();
    });

    it('renders multiple templates', () => {
        const renders = [makeRender({template: 'base.html.twig'}), makeRender({template: 'header.html.twig'})];
        renderWithProviders(<TemplatePanel data={makeData({renders, renderCount: 2})} />);
        expect(screen.getByText('2 renders')).toBeInTheDocument();
        expect(screen.getByText('base.html.twig')).toBeInTheDocument();
        expect(screen.getByText('header.html.twig')).toBeInTheDocument();
    });

    it('filters templates by name', async () => {
        const user = userEvent.setup();
        const renders = [makeRender({template: 'base.html.twig'}), makeRender({template: 'header.html.twig'})];
        renderWithProviders(<TemplatePanel data={makeData({renders, renderCount: 2})} />);
        await user.type(screen.getByPlaceholderText('Filter templates...'), 'header');
        expect(screen.getByText('header.html.twig')).toBeInTheDocument();
        expect(screen.queryByText('base.html.twig')).not.toBeInTheDocument();
    });

    it('renders file basename for file paths', () => {
        renderWithProviders(
            <TemplatePanel
                data={makeData({
                    renders: [makeRender({template: '/app/views/home/index.php', renderTime: 0})],
                    totalTime: 0,
                })}
            />,
        );
        expect(screen.getByText('index.php')).toBeInTheDocument();
        expect(screen.getByText('/app/views/home')).toBeInTheDocument();
    });

    it('renders parameters chip when present', () => {
        renderWithProviders(
            <TemplatePanel
                data={makeData({
                    renders: [makeRender({template: '/app/views/index.php', parameters: [{name: 'title'}]})],
                })}
            />,
        );
        expect(screen.getByText('1 param')).toBeInTheDocument();
    });

    it('does not show params chip when parameters empty', () => {
        renderWithProviders(
            <TemplatePanel
                data={makeData({renders: [makeRender({template: '/app/views/index.php', parameters: []})]})}
            />,
        );
        expect(screen.queryByText(/param/)).not.toBeInTheDocument();
    });

    it('expands entry on click showing output', async () => {
        const user = userEvent.setup();
        renderWithProviders(
            <TemplatePanel
                data={makeData({
                    renders: [
                        makeRender({
                            template: '/app/views/home/index.php',
                            output: '<p>Test content</p>',
                            renderTime: 0,
                        }),
                    ],
                    totalTime: 0,
                })}
            />,
        );
        await user.click(screen.getByText('index.php'));
        expect(screen.getByText('Output')).toBeInTheDocument();
        expect(screen.getByText('<p>Test content</p>')).toBeInTheDocument();
    });
});
