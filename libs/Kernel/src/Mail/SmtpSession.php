<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Mail;

/**
 * SMTP protocol state machine. Transport-agnostic: feed raw bytes via feed(), read
 * response bytes from the returned string, and pop completed messages with takeCompletedMessage().
 *
 * Implements a minimal RFC 5321 subset sufficient for dev-mail capture:
 * HELO, EHLO, MAIL FROM, RCPT TO, DATA, RSET, NOOP, QUIT, VRFY (rejected), AUTH (accept any).
 *
 * STARTTLS is not implemented — dev listeners bind on localhost only. Clients that force TLS
 * must be configured with tls=false in their DSN.
 */
final class SmtpSession
{
    private const int STATE_INIT = 0;
    private const int STATE_READY = 1;
    private const int STATE_MAIL = 2;
    private const int STATE_RCPT = 3;
    private const int STATE_DATA = 4;
    private const int STATE_CLOSED = 5;
    private const int STATE_AUTH_LOGIN_USER = 6;
    private const int STATE_AUTH_LOGIN_PASS = 7;

    private const int MAX_LINE_LENGTH = 1000;

    private string $inputBuffer = '';
    private string $dataBuffer = '';
    private int $state = self::STATE_INIT;
    private bool $hasHelo = false;

    private ?string $mailFrom = null;

    /** @var list<string> */
    private array $rcptTo = [];

    /** @var list<array{from: ?string, rcpt: list<string>, raw: string}> */
    private array $completedEnvelopes = [];

    public function __construct(
        private readonly string $hostname = 'adp-smtp',
        private readonly int $maxMessageSize = 20 * 1024 * 1024,
    ) {}

    /**
     * Initial 220 greeting sent on connection open.
     */
    public function greeting(): string
    {
        if ($this->state !== self::STATE_INIT) {
            return '';
        }
        $this->state = self::STATE_READY;
        return '220 ' . $this->hostname . " ADP SMTP ready\r\n";
    }

    /**
     * Feed raw bytes received from the socket. Returns bytes to send back to the client.
     * Multiple SMTP commands may be present (pipelining) — all are processed.
     */
    public function feed(string $bytes): string
    {
        if ($this->state === self::STATE_CLOSED) {
            return '';
        }

        $this->inputBuffer .= $bytes;
        $output = '';

        while (true) {
            if ($this->state === self::STATE_DATA) {
                $terminated = $this->extractDataTerminator();
                if ($terminated === null) {
                    if (strlen($this->dataBuffer) > $this->maxMessageSize) {
                        $this->dataBuffer = '';
                        $this->resetTransaction();
                        $output .= "552 Message size exceeds fixed maximum\r\n";
                        continue;
                    }
                    break;
                }
                $output .= $this->finalizeData();
                continue;
            }

            $lineEnd = strpos($this->inputBuffer, "\n");
            if ($lineEnd === false) {
                if (strlen($this->inputBuffer) > self::MAX_LINE_LENGTH) {
                    $this->inputBuffer = '';
                    $output .= "500 Line too long\r\n";
                }
                break;
            }

            $line = substr($this->inputBuffer, 0, $lineEnd + 1);
            $this->inputBuffer = substr($this->inputBuffer, $lineEnd + 1);
            $line = rtrim($line, "\r\n");

            if (strlen($line) > self::MAX_LINE_LENGTH) {
                $output .= "500 Line too long\r\n";
                continue;
            }

            $output .= $this->processLine($line);

            if ($this->state === self::STATE_CLOSED) {
                break;
            }
        }

        return $output;
    }

    public function isClosed(): bool
    {
        return $this->state === self::STATE_CLOSED;
    }

    public function close(): void
    {
        $this->state = self::STATE_CLOSED;
    }

    public function hasCompletedMessage(): bool
    {
        return $this->completedEnvelopes !== [];
    }

    /**
     * Pop the oldest completed envelope (or null). Envelope format:
     * ['from' => string|null, 'rcpt' => string[], 'raw' => string]
     *
     * @return array{from: ?string, rcpt: list<string>, raw: string}|null
     */
    public function takeCompletedMessage(): ?array
    {
        return array_shift($this->completedEnvelopes);
    }

    private function processLine(string $line): string
    {
        if ($this->state === self::STATE_AUTH_LOGIN_USER) {
            $this->state = self::STATE_AUTH_LOGIN_PASS;
            return "334 UGFzc3dvcmQ6\r\n";
        }
        if ($this->state === self::STATE_AUTH_LOGIN_PASS) {
            $this->state = self::STATE_READY;
            return "235 Authentication succeeded\r\n";
        }

        if ($line === '') {
            return "500 Empty command\r\n";
        }

        $spacePos = strpos($line, ' ');
        $verb = $spacePos === false ? strtoupper($line) : strtoupper(substr($line, 0, $spacePos));
        $args = $spacePos === false ? '' : ltrim(substr($line, $spacePos + 1));

        return match ($verb) {
            'HELO' => $this->handleHelo($args),
            'EHLO' => $this->handleEhlo($args),
            'MAIL' => $this->handleMail($args),
            'RCPT' => $this->handleRcpt($args),
            'DATA' => $this->handleData(),
            'RSET' => $this->handleRset(),
            'NOOP' => "250 OK\r\n",
            'QUIT' => $this->handleQuit(),
            'AUTH' => $this->handleAuth($args),
            'STARTTLS' => "502 STARTTLS not supported; use tls=false on localhost\r\n",
            'VRFY', 'EXPN' => "502 Command not implemented\r\n",
            'HELP' => "214 ADP SMTP listener — no delivery, messages captured for debug\r\n",
            default => "500 Command not recognized\r\n",
        };
    }

    private function handleHelo(string $args): string
    {
        if ($args === '') {
            return "501 HELO requires domain\r\n";
        }
        $this->resetTransaction();
        $this->hasHelo = true;
        return '250 ' . $this->hostname . "\r\n";
    }

    private function handleEhlo(string $args): string
    {
        if ($args === '') {
            return "501 EHLO requires domain\r\n";
        }
        $this->resetTransaction();
        $this->hasHelo = true;
        return (
            "250-{$this->hostname}\r\n"
            . '250-SIZE '
            . $this->maxMessageSize
            . "\r\n"
            . "250-8BITMIME\r\n"
            . "250-SMTPUTF8\r\n"
            . "250-PIPELINING\r\n"
            . "250-AUTH PLAIN LOGIN\r\n"
            . "250 HELP\r\n"
        );
    }

    private function handleMail(string $args): string
    {
        if (!$this->hasHelo || $this->state !== self::STATE_READY) {
            return "503 Bad sequence of commands\r\n";
        }
        if (!preg_match('/^FROM:\s*<([^>]*)>/i', $args, $m)) {
            return "501 Syntax: MAIL FROM:<address>\r\n";
        }
        if (preg_match('/\bSIZE=(\d+)/i', $args, $sizeMatch)) {
            $size = (int) $sizeMatch[1];
            if ($size > $this->maxMessageSize) {
                return "552 Message size exceeds fixed maximum\r\n";
            }
        }
        $this->mailFrom = $m[1];
        $this->rcptTo = [];
        $this->state = self::STATE_MAIL;
        return "250 OK\r\n";
    }

    private function handleRcpt(string $args): string
    {
        if ($this->state !== self::STATE_MAIL && $this->state !== self::STATE_RCPT) {
            return "503 Bad sequence of commands\r\n";
        }
        if (!preg_match('/^TO:\s*<([^>]+)>/i', $args, $m)) {
            return "501 Syntax: RCPT TO:<address>\r\n";
        }
        $this->rcptTo[] = $m[1];
        $this->state = self::STATE_RCPT;
        return "250 OK\r\n";
    }

    private function handleData(): string
    {
        if ($this->state !== self::STATE_RCPT) {
            return "503 Bad sequence of commands\r\n";
        }
        $this->dataBuffer = '';
        $this->state = self::STATE_DATA;
        return "354 Start mail input; end with <CRLF>.<CRLF>\r\n";
    }

    private function handleRset(): string
    {
        $this->resetTransaction();
        return "250 OK\r\n";
    }

    private function handleQuit(): string
    {
        $this->state = self::STATE_CLOSED;
        return '221 ' . $this->hostname . " closing\r\n";
    }

    private function handleAuth(string $args): string
    {
        $parts = explode(' ', $args, 2);
        $mechanism = strtoupper($parts[0] ?? '');
        return match ($mechanism) {
            'PLAIN' => "235 Authentication succeeded\r\n",
            'LOGIN' => $this->beginAuthLogin(),
            default => "504 Unrecognized authentication type\r\n",
        };
    }

    private function beginAuthLogin(): string
    {
        $this->state = self::STATE_AUTH_LOGIN_USER;
        return "334 VXNlcm5hbWU6\r\n";
    }

    /**
     * Scan inputBuffer for RFC 5321 DATA terminator \r\n.\r\n. Moves consumed bytes into dataBuffer.
     * Returns true when terminator found (dataBuffer finalized), false otherwise.
     */
    private function extractDataTerminator(): ?bool
    {
        $buffer = $this->dataBuffer . $this->inputBuffer;
        $needle = "\r\n.\r\n";
        $pos = strpos($buffer, $needle);
        if ($pos === false) {
            $keep = max(0, strlen($buffer) - 4);
            $this->dataBuffer = substr($buffer, 0, $keep);
            $this->inputBuffer = substr($buffer, $keep);
            return null;
        }
        $this->dataBuffer = substr($buffer, 0, $pos);
        $this->inputBuffer = substr($buffer, $pos + strlen($needle));
        return true;
    }

    private function finalizeData(): string
    {
        $raw = $this->unstuffDots($this->dataBuffer);
        $this->completedEnvelopes[] = [
            'from' => $this->mailFrom,
            'rcpt' => $this->rcptTo,
            'raw' => $raw,
        ];
        $this->dataBuffer = '';
        $this->resetTransaction();
        return "250 OK message accepted\r\n";
    }

    /**
     * Reverse RFC 5321 dot-stuffing: lines starting with ".." become lines starting with ".".
     */
    private function unstuffDots(string $data): string
    {
        return preg_replace("/(^|\r\n)\\.\\./", '$1.', $data) ?? $data;
    }

    private function resetTransaction(): void
    {
        $this->mailFrom = null;
        $this->rcptTo = [];
        $this->state = self::STATE_READY;
    }
}
