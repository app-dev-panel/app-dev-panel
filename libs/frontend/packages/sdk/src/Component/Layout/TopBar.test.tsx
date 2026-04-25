import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it, vi} from 'vitest';
import {renderWithProviders} from '../../test-utils';
import {TopBar} from './TopBar';

describe('TopBar', () => {
    it('renders logo', () => {
        renderWithProviders(<TopBar />);
        expect(screen.getByText('App Dev Panel')).toBeInTheDocument();
    });

    it('renders search trigger', () => {
        renderWithProviders(<TopBar />);
        expect(screen.getByText(/Search/)).toBeInTheDocument();
    });

    it('renders request info when all props provided', () => {
        renderWithProviders(<TopBar method="GET" path="/test" status={200} duration="5 ms" />);
        expect(screen.getByText('GET')).toBeInTheDocument();
        expect(screen.getByText('/test')).toBeInTheDocument();
        expect(screen.getByText('200')).toBeInTheDocument();
    });

    it('hides request info when props are missing', () => {
        renderWithProviders(<TopBar method="GET" />);
        expect(screen.queryByText('GET')).not.toBeInTheDocument();
    });

    it('shows light_mode icon by default', () => {
        renderWithProviders(<TopBar />);
        expect(screen.getByText('light_mode')).toBeInTheDocument();
    });

    it('shows dark_mode icon when mode is dark', () => {
        renderWithProviders(<TopBar mode="dark" />);
        expect(screen.getByText('dark_mode')).toBeInTheDocument();
    });

    it('calls navigation handlers', async () => {
        const user = userEvent.setup();
        const onPrev = vi.fn();
        const onNext = vi.fn();
        renderWithProviders(
            <TopBar method="GET" path="/test" status={200} duration="5 ms" onPrevEntry={onPrev} onNextEntry={onNext} />,
        );
        const buttons = screen.getAllByRole('button');
        // Find prev/next buttons (they contain chevron icons)
        const prevButton = buttons.find((b) => b.textContent?.includes('chevron_left'));
        const nextButton = buttons.find((b) => b.textContent?.includes('chevron_right'));
        if (prevButton) await user.click(prevButton);
        if (nextButton) await user.click(nextButton);
        expect(onPrev).toHaveBeenCalledOnce();
        expect(onNext).toHaveBeenCalledOnce();
    });

    it('shows editor integration section in settings dialog', async () => {
        const user = userEvent.setup();
        renderWithProviders(<TopBar editorConfig={{editor: 'none', customUrlTemplate: '', pathMapping: {}}} />);

        // Open more menu
        const moreButton = screen.getAllByRole('button').find((b) => b.textContent?.includes('more_vert'));
        expect(moreButton).toBeDefined();
        await user.click(moreButton!);

        // Click settings
        const settingsItem = screen.getByText('Settings');
        await user.click(settingsItem);

        // Editor Integration section is visible
        expect(screen.getByText('Editor Integration')).toBeInTheDocument();
        // The autocomplete shows the current preset label
        expect(screen.getByLabelText('Editor')).toHaveValue('None (File Explorer only)');
    });

    it('shows custom template field when editor is custom', async () => {
        renderWithProviders(
            <TopBar
                editorConfig={{editor: 'custom', customUrlTemplate: 'myeditor://{file}:{line}', pathMapping: {}}}
            />,
        );

        const user = userEvent.setup();
        const moreButton = screen.getAllByRole('button').find((b) => b.textContent?.includes('more_vert'));
        await user.click(moreButton!);
        await user.click(screen.getByText('Settings'));

        expect(screen.getByLabelText('Custom URL template')).toBeInTheDocument();
        expect(screen.getByDisplayValue('myeditor://{file}:{line}')).toBeInTheDocument();
    });

    it('hides custom template field when editor is not custom', async () => {
        renderWithProviders(<TopBar editorConfig={{editor: 'phpstorm', customUrlTemplate: '', pathMapping: {}}} />);

        const user = userEvent.setup();
        const moreButton = screen.getAllByRole('button').find((b) => b.textContent?.includes('more_vert'));
        await user.click(moreButton!);
        await user.click(screen.getByText('Settings'));

        expect(screen.queryByLabelText('Custom URL template')).not.toBeInTheDocument();
    });

    it('renders external links to donate, website and github in more menu', async () => {
        const user = userEvent.setup();
        renderWithProviders(<TopBar />);

        const moreButton = screen.getAllByRole('button').find((b) => b.textContent?.includes('more_vert'));
        await user.click(moreButton!);

        const donate = screen.getByRole('menuitem', {name: /Donate/});
        expect(donate).toHaveAttribute('href', 'https://app-dev-panel.github.io/app-dev-panel/sponsor');
        expect(donate).toHaveAttribute('target', '_blank');
        expect(donate).toHaveAttribute('rel', 'noopener noreferrer');

        const website = screen.getByRole('menuitem', {name: /Website/});
        expect(website).toHaveAttribute('href', 'https://app-dev-panel.github.io/app-dev-panel/');
        expect(website).toHaveAttribute('target', '_blank');

        const github = screen.getByRole('menuitem', {name: /GitHub/});
        expect(github).toHaveAttribute('href', 'https://github.com/app-dev-panel/app-dev-panel');
        expect(github).toHaveAttribute('target', '_blank');
    });

    it('calls onEditorConfigChange with patched template when edited', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        renderWithProviders(
            <TopBar
                editorConfig={{editor: 'custom', customUrlTemplate: '', pathMapping: {}}}
                onEditorConfigChange={onChange}
            />,
        );

        const moreButton = screen.getAllByRole('button').find((b) => b.textContent?.includes('more_vert'));
        await user.click(moreButton!);
        await user.click(screen.getByText('Settings'));

        const input = screen.getByLabelText('Custom URL template');
        await user.type(input, 'x');
        expect(onChange).toHaveBeenCalledWith({editor: 'custom', customUrlTemplate: 'x', pathMapping: {}});
    });
});
