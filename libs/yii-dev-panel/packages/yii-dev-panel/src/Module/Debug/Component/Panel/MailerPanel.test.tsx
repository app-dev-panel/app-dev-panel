import {screen} from '@testing-library/react';
import {renderWithProviders} from '@yiisoft/yii-dev-panel-sdk/test-utils';
import {describe, expect, it} from 'vitest';
import {MailerPanel} from './MailerPanel';

describe('MailerPanel', () => {
    it('shows empty message when no messages', () => {
        renderWithProviders(<MailerPanel data={{messages: []}} />);
        expect(screen.getByText(/No dumped mails/)).toBeInTheDocument();
    });

    it('renders mail messages', () => {
        const data = {
            messages: [
                {
                    from: {'user@example.com': 'User'},
                    to: {'admin@example.com': 'Admin'},
                    subject: 'Test Email Subject',
                    date: '2024-01-15',
                    textBody: 'Hello',
                    htmlBody: '<p>Hello</p>',
                    raw: 'raw content',
                    charset: 'utf-8',
                    replyTo: {},
                    cc: {},
                    bcc: {},
                },
            ],
        };
        renderWithProviders(<MailerPanel data={data} />);
        expect(screen.getByText('Test Email Subject')).toBeInTheDocument();
    });
});
