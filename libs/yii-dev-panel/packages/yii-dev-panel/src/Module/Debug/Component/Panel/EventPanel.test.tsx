import {screen} from '@testing-library/react';
import {renderWithProviders} from '@yiisoft/yii-dev-panel-sdk/test-utils';
import {describe, expect, it} from 'vitest';
import {EventPanel} from './EventPanel';

describe('EventPanel', () => {
    const preloadedState = {'store.debug': {entry: {id: 'test-1', collectors: []}, currentPageRequestIds: []}};

    it('shows empty message when no events', () => {
        renderWithProviders(<EventPanel events={[]} />);
        expect(screen.getByText(/No dispatched events/)).toBeInTheDocument();
    });

    it('shows empty message when events is null', () => {
        renderWithProviders(<EventPanel events={null as any} />);
        expect(screen.getByText(/No dispatched events/)).toBeInTheDocument();
    });

    it('renders event items', () => {
        const events = [
            {
                event: '{}',
                file: '/src/Handler.php',
                line: '/src/Handler.php:15',
                name: 'app.startup',
                time: 1705319445.123456,
            },
        ];
        renderWithProviders(<EventPanel events={events} />, {preloadedState});
        expect(screen.getByText('app.startup')).toBeInTheDocument();
    });
});
