import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it} from 'vitest';
import {MailerPanel} from './MailerPanel';

const makeMessage = (overrides: Partial<Parameters<typeof MailerPanel>[0]['data']['messages'][0]> = {}) => ({
    from: {'sender@example.com': 'Sender'},
    to: {'admin@example.com': 'Admin'},
    subject: 'Test Email Subject',
    date: '2024-01-15 10:30:00',
    textBody: 'Hello plain text',
    htmlBody: '<p>Hello HTML</p>',
    raw: 'From: sender@example.com\r\nTo: admin@example.com\r\n\r\nHello',
    charset: 'utf-8',
    replyTo: {},
    cc: {},
    bcc: {},
    ...overrides,
});

describe('MailerPanel', () => {
    it('shows empty message when no messages', () => {
        renderWithProviders(<MailerPanel data={{messages: []}} />);
        expect(screen.getByText(/No dumped mails/)).toBeInTheDocument();
    });

    it('renders section title with count (singular)', () => {
        renderWithProviders(<MailerPanel data={{messages: [makeMessage()]}} />);
        expect(screen.getByText('1 message')).toBeInTheDocument();
    });

    it('renders section title with count (plural)', () => {
        renderWithProviders(<MailerPanel data={{messages: [makeMessage(), makeMessage({subject: 'Second'})]}} />);
        expect(screen.getByText('2 messages')).toBeInTheDocument();
    });

    it('renders email subject', () => {
        renderWithProviders(<MailerPanel data={{messages: [makeMessage({subject: 'Welcome aboard'})]}} />);
        expect(screen.getByText('Welcome aboard')).toBeInTheDocument();
    });

    it('renders To field in row', () => {
        renderWithProviders(<MailerPanel data={{messages: [makeMessage()]}} />);
        expect(screen.getAllByText(/admin@example\.com/).length).toBeGreaterThan(0);
    });

    it('renders date in row', () => {
        renderWithProviders(<MailerPanel data={{messages: [makeMessage({date: '2024-03-20'})]}} />);
        expect(screen.getByText('2024-03-20')).toBeInTheDocument();
    });

    it('renders index badges', () => {
        renderWithProviders(<MailerPanel data={{messages: [makeMessage(), makeMessage({subject: 'Second'})]}} />);
        expect(screen.getByText('1')).toBeInTheDocument();
        expect(screen.getByText('2')).toBeInTheDocument();
    });

    it('expands detail on click showing From/To/Charset', async () => {
        const user = userEvent.setup();
        renderWithProviders(<MailerPanel data={{messages: [makeMessage()]}} />);
        await user.click(screen.getByText('Test Email Subject'));
        expect(screen.getByText('From')).toBeInTheDocument();
        expect(screen.getByText('Charset')).toBeInTheDocument();
        expect(screen.getByText('utf-8')).toBeInTheDocument();
    });

    it('shows CC when present', async () => {
        const user = userEvent.setup();
        const msg = makeMessage({cc: {'cc@example.com': 'CC User'}});
        renderWithProviders(<MailerPanel data={{messages: [msg]}} />);
        await user.click(screen.getByText('Test Email Subject'));
        expect(screen.getByText('CC')).toBeInTheDocument();
    });

    it('hides CC when empty', async () => {
        const user = userEvent.setup();
        renderWithProviders(<MailerPanel data={{messages: [makeMessage()]}} />);
        await user.click(screen.getByText('Test Email Subject'));
        expect(screen.queryByText('CC')).not.toBeInTheDocument();
    });

    it('shows Preview HTML and View Raw chips', async () => {
        const user = userEvent.setup();
        renderWithProviders(<MailerPanel data={{messages: [makeMessage()]}} />);
        await user.click(screen.getByText('Test Email Subject'));
        expect(screen.getByText('Preview HTML')).toBeInTheDocument();
        expect(screen.getByText('View Raw')).toBeInTheDocument();
    });
});
