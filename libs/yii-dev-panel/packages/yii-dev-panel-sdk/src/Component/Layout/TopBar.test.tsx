import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it, vi} from 'vitest';
import {renderWithProviders} from '../../test-utils';
import {TopBar} from './TopBar';

describe('TopBar', () => {
    it('renders logo', () => {
        renderWithProviders(<TopBar />);
        expect(screen.getByText('ADP')).toBeInTheDocument();
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
});
