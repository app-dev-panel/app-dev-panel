import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it} from 'vitest';
import {MailerPanel} from './MailerPanel';

type Message = Parameters<typeof MailerPanel>[0]['data']['messages'][0];

const makeMessage = (overrides: Partial<Message> = {}): Message => ({
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
    messageId: null,
    headers: {},
    size: 128,
    attachments: [],
    ...overrides,
});

describe('MailerPanel — list view', () => {
    it('shows empty state when no messages', () => {
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

    it('renders subject and To summary on each row', () => {
        renderWithProviders(<MailerPanel data={{messages: [makeMessage({subject: 'Welcome aboard'})]}} />);
        expect(screen.getByText('Welcome aboard')).toBeInTheDocument();
        expect(screen.getByText(/admin@example\.com/)).toBeInTheDocument();
    });

    it('renders index badges per row', () => {
        renderWithProviders(<MailerPanel data={{messages: [makeMessage(), makeMessage({subject: 'Second'})]}} />);
        expect(screen.getByText('1')).toBeInTheDocument();
        expect(screen.getByText('2')).toBeInTheDocument();
    });

    it('renders attachment count badge when there are non-inline attachments', () => {
        const message = makeMessage({
            attachments: [
                {
                    filename: 'a.txt',
                    contentType: 'text/plain',
                    size: 8,
                    contentId: null,
                    inline: false,
                    contentBase64: '',
                },
                {
                    filename: 'b.pdf',
                    contentType: 'application/pdf',
                    size: 8,
                    contentId: null,
                    inline: false,
                    contentBase64: '',
                },
            ],
        });
        renderWithProviders(<MailerPanel data={{messages: [message]}} />);
        expect(screen.getByText('2 files')).toBeInTheDocument();
    });

    it('renders an HTML thumbnail iframe for each message that has HTML body', () => {
        const {container} = renderWithProviders(<MailerPanel data={{messages: [makeMessage()]}} />);
        const iframe = container.querySelector('iframe[title="preview"]');
        expect(iframe).not.toBeNull();
    });

    it('renders a text snippet when message has no HTML body', () => {
        renderWithProviders(
            <MailerPanel data={{messages: [makeMessage({htmlBody: null, textBody: 'Plain only body'})]}} />,
        );
        expect(screen.getByText(/Plain only body/)).toBeInTheDocument();
    });
});

describe('MailerPanel — detail view', () => {
    it('opens the detail view when a row is clicked', async () => {
        const user = userEvent.setup();
        renderWithProviders(<MailerPanel data={{messages: [makeMessage({subject: 'Click me'})]}} />);
        await user.click(screen.getByText('Click me'));
        expect(screen.getByText(/Message 1 of 1/)).toBeInTheDocument();
        // Recipients block shows a "From" label row
        expect(screen.getByText('From')).toBeInTheDocument();
    });

    it('pre-selects message from ?message query param (simulates page refresh)', () => {
        renderWithProviders(
            <MailerPanel data={{messages: [makeMessage({subject: 'Second'}), makeMessage({subject: 'Third'})]}} />,
            {route: '/?message=1'},
        );
        expect(screen.getByText(/Message 2 of 2/)).toBeInTheDocument();
        expect(screen.getByText('Third')).toBeInTheDocument();
    });

    it('ignores out-of-range ?message values and falls back to list', () => {
        renderWithProviders(<MailerPanel data={{messages: [makeMessage()]}} />, {route: '/?message=99'});
        expect(screen.getByText('1 message')).toBeInTheDocument();
    });

    it('returns to the list when Back is clicked', async () => {
        const user = userEvent.setup();
        renderWithProviders(<MailerPanel data={{messages: [makeMessage({subject: 'Click me'})]}} />);
        await user.click(screen.getByText('Click me'));
        await user.click(screen.getByRole('button', {name: /back/i}));
        expect(screen.getByText('1 message')).toBeInTheDocument();
    });

    it('renders preview tabs and viewport toggle', async () => {
        const user = userEvent.setup();
        renderWithProviders(<MailerPanel data={{messages: [makeMessage()]}} />);
        await user.click(screen.getByText('Test Email Subject'));
        expect(screen.getByRole('tab', {name: /HTML Preview/i})).toBeInTheDocument();
        expect(screen.getByRole('tab', {name: /HTML Source/i})).toBeInTheDocument();
        expect(screen.getByRole('tab', {name: /^Text$/i})).toBeInTheDocument();
        expect(screen.getByRole('tab', {name: /^Raw$/i})).toBeInTheDocument();
        expect(screen.getByRole('button', {name: /desktop/i})).toBeInTheDocument();
        expect(screen.getByRole('button', {name: /tablet/i})).toBeInTheDocument();
        expect(screen.getByRole('button', {name: /mobile/i})).toBeInTheDocument();
    });

    it('switches to text tab and shows the text body', async () => {
        const user = userEvent.setup();
        renderWithProviders(<MailerPanel data={{messages: [makeMessage({textBody: 'Plain content here'})]}} />);
        await user.click(screen.getByText('Test Email Subject'));
        await user.click(screen.getByRole('tab', {name: /^Text$/i}));
        expect(screen.getByText('Plain content here')).toBeInTheDocument();
    });

    it('shows the raw content under the Raw tab', async () => {
        const user = userEvent.setup();
        renderWithProviders(<MailerPanel data={{messages: [makeMessage({raw: 'RAW-MESSAGE-CONTENT-Z'})]}} />);
        await user.click(screen.getByText('Test Email Subject'));
        await user.click(screen.getByRole('tab', {name: /^Raw$/i}));
        expect(screen.getByText(/RAW-MESSAGE-CONTENT-Z/)).toBeInTheDocument();
    });

    it('renders From/To rows only for populated address maps', async () => {
        const user = userEvent.setup();
        renderWithProviders(<MailerPanel data={{messages: [makeMessage()]}} />);
        await user.click(screen.getByText('Test Email Subject'));
        expect(screen.getByText('From')).toBeInTheDocument();
        expect(screen.getByText('To')).toBeInTheDocument();
        expect(screen.queryByText('CC')).not.toBeInTheDocument();
        expect(screen.queryByText('BCC')).not.toBeInTheDocument();
    });

    it('shows CC row only when CC is populated', async () => {
        const user = userEvent.setup();
        const cc = makeMessage({cc: {'cc@example.com': 'CC User'}});
        renderWithProviders(<MailerPanel data={{messages: [cc]}} />);
        await user.click(screen.getByText('Test Email Subject'));
        expect(screen.getByText('CC')).toBeInTheDocument();
    });

    it('renders attachments with download links', async () => {
        const user = userEvent.setup();
        const message = makeMessage({
            attachments: [
                {
                    filename: 'release.txt',
                    contentType: 'text/plain',
                    size: 5,
                    contentId: null,
                    inline: false,
                    contentBase64: 'aGVsbG8=',
                },
            ],
        });
        renderWithProviders(<MailerPanel data={{messages: [message]}} />);
        await user.click(screen.getByText('Test Email Subject'));
        const downloadLink = screen.getByRole('link', {name: /Download release\.txt/i});
        expect(downloadLink).toHaveAttribute('href', 'data:text/plain;base64,aGVsbG8=');
        expect(downloadLink).toHaveAttribute('download', 'release.txt');
    });

    it('extracts links from the HTML body', async () => {
        const user = userEvent.setup();
        const message = makeMessage({
            htmlBody: '<a href="https://example.com/a">A</a><a href="https://example.com/b">B</a>',
        });
        renderWithProviders(<MailerPanel data={{messages: [message]}} />);
        await user.click(screen.getByText('Test Email Subject'));
        expect(screen.getByText('Links: 2')).toBeInTheDocument();
        expect(screen.getByText('https://example.com/a')).toBeInTheDocument();
        expect(screen.getByText('https://example.com/b')).toBeInTheDocument();
    });

    it('headers chip expands a collapsible headers block', async () => {
        const user = userEvent.setup();
        const message = makeMessage({messageId: '<abc@example.com>', headers: {'X-Custom': 'yes'}});
        renderWithProviders(<MailerPanel data={{messages: [message]}} />);
        await user.click(screen.getByText('Test Email Subject'));
        const headersChip = screen.getByText(/^Headers · 1$/);
        expect(headersChip).toBeInTheDocument();
        // Initially collapsed
        expect(screen.queryByText('X-Custom')).not.toBeInTheDocument();
        await user.click(headersChip);
        expect(screen.getByText('X-Custom')).toBeInTheDocument();
        expect(screen.getByText('yes')).toBeInTheDocument();
    });

    it('opens a fullscreen preview dialog when the fullscreen button is clicked', async () => {
        const user = userEvent.setup();
        renderWithProviders(<MailerPanel data={{messages: [makeMessage()]}} />);
        await user.click(screen.getByText('Test Email Subject'));
        const fullscreenButton = screen.getByRole('button', {name: /open preview fullscreen/i});
        await user.click(fullscreenButton);
        const dialog = await screen.findByRole('dialog');
        expect(within(dialog).getByRole('button', {name: /close fullscreen preview/i})).toBeInTheDocument();
    });

    it('does not crash when optional fields are missing (legacy payloads)', async () => {
        const user = userEvent.setup();
        const minimal = {
            from: {'a@a.com': ''},
            to: {'b@b.com': ''},
            subject: 'Legacy',
            textBody: 'plain',
            htmlBody: null,
            raw: 'legacy raw',
            charset: 'utf-8',
            replyTo: {},
            cc: {},
            bcc: {},
            date: null,
            // deliberately no messageId/headers/size/attachments — simulates old entries on disk
        } as unknown as Message;
        renderWithProviders(<MailerPanel data={{messages: [minimal]}} />);
        await user.click(screen.getByText('Legacy'));
        expect(screen.getByText(/Message 1 of 1/)).toBeInTheDocument();
        // Headers chip should not appear
        expect(screen.queryByText(/^Headers · /)).not.toBeInTheDocument();
    });
});
