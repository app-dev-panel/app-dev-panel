<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Mail;

/**
 * Minimal RFC 5322 / MIME parser for captured mail. Produces the same normalized
 * message array shape that {@see \AppDevPanel\Kernel\Collector\MailerCollector}
 * expects from framework adapters.
 *
 * Supported: text/plain, text/html, multipart/alternative, multipart/mixed, multipart/related,
 * base64 and quoted-printable transfer encodings, RFC 2047 encoded-word headers.
 * Attachments: metadata only (filename, mime, size) — body bytes are not retained.
 */
final class MimeParser
{
    /**
     * @return array{
     *     from: array<string, string>,
     *     to: array<string, string>,
     *     cc: array<string, string>,
     *     bcc: array<string, string>,
     *     replyTo: array<string, string>,
     *     subject: string,
     *     textBody: ?string,
     *     htmlBody: ?string,
     *     raw: string,
     *     charset: string,
     *     date: ?string,
     *     messageId: ?string,
     *     sessionId: ?string,
     *     attachments: list<array{filename: ?string, mime: string, size: int, contentId: ?string}>,
     *     headers: array<string, string>,
     * }
     */
    public function parse(string $raw): array
    {
        [$headerBlock, $body] = $this->splitHeadersBody($raw);
        $headers = $this->parseHeaders($headerBlock);

        $contentType = $headers['content-type'] ?? 'text/plain';
        [$mainType, $ctParams] = $this->parseContentType($contentType);
        $charset = $ctParams['charset'] ?? 'utf-8';

        $textBody = null;
        $htmlBody = null;
        $attachments = [];

        if (str_starts_with($mainType, 'multipart/') && isset($ctParams['boundary'])) {
            $parts = $this->splitMultipart($body, $ctParams['boundary']);
            foreach ($parts as $part) {
                $this->absorbPart($part, $textBody, $htmlBody, $attachments);
            }
        } else {
            $encoding = strtolower($headers['content-transfer-encoding'] ?? '7bit');
            $decoded = $this->decodeBody($body, $encoding);
            if (str_starts_with($mainType, 'text/html')) {
                $htmlBody = $decoded;
            } else {
                $textBody = $decoded;
            }
        }

        return [
            'from' => $this->parseAddressList($headers['from'] ?? ''),
            'to' => $this->parseAddressList($headers['to'] ?? ''),
            'cc' => $this->parseAddressList($headers['cc'] ?? ''),
            'bcc' => $this->parseAddressList($headers['bcc'] ?? ''),
            'replyTo' => $this->parseAddressList($headers['reply-to'] ?? ''),
            'subject' => $this->decodeMimeHeader($headers['subject'] ?? ''),
            'textBody' => $textBody,
            'htmlBody' => $htmlBody,
            'raw' => $raw,
            'charset' => $charset,
            'date' => isset($headers['date']) ? $this->decodeMimeHeader($headers['date']) : null,
            'messageId' => isset($headers['message-id']) ? trim($headers['message-id'], " \t<>") : null,
            'sessionId' => $headers['x-adp-session-id'] ?? null,
            'attachments' => $attachments,
            'headers' => $headers,
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitHeadersBody(string $raw): array
    {
        $sep = "\r\n\r\n";
        $pos = strpos($raw, $sep);
        if ($pos === false) {
            $sep = "\n\n";
            $pos = strpos($raw, $sep);
        }
        if ($pos === false) {
            return [$raw, ''];
        }
        return [substr($raw, 0, $pos), substr($raw, $pos + strlen($sep))];
    }

    /**
     * Parse headers with RFC 5322 folding (continuation lines start with whitespace).
     *
     * @return array<string, string>
     */
    private function parseHeaders(string $block): array
    {
        $lines = preg_split('/\r?\n/', $block) ?: [];
        $unfolded = [];
        $current = '';
        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }
            if ($line[0] === ' ' || $line[0] === "\t") {
                $current .= ' ' . ltrim($line);
            } else {
                if ($current !== '') {
                    $unfolded[] = $current;
                }
                $current = $line;
            }
        }
        if ($current !== '') {
            $unfolded[] = $current;
        }

        $headers = [];
        foreach ($unfolded as $line) {
            $colon = strpos($line, ':');
            if ($colon === false) {
                continue;
            }
            $name = strtolower(trim(substr($line, 0, $colon)));
            $value = trim(substr($line, $colon + 1));
            $headers[$name] = $value;
        }
        return $headers;
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function parseContentType(string $value): array
    {
        $parts = explode(';', $value);
        $main = strtolower(trim(array_shift($parts) ?? ''));
        $params = [];
        foreach ($parts as $p) {
            $eq = strpos($p, '=');
            if ($eq === false) {
                continue;
            }
            $k = strtolower(trim(substr($p, 0, $eq)));
            $v = trim(substr($p, $eq + 1), " \t\"");
            $params[$k] = $v;
        }
        return [$main, $params];
    }

    /**
     * @return list<string>
     */
    private function splitMultipart(string $body, string $boundary): array
    {
        $marker = '--' . $boundary;
        $end = '--' . $boundary . '--';

        $pieces = explode($marker, $body);
        array_shift($pieces); // preamble

        $parts = [];
        foreach ($pieces as $piece) {
            if (str_starts_with($piece, '--')) {
                break;
            }
            $piece = preg_replace('/^\r?\n/', '', $piece) ?? $piece;
            $piece = preg_replace('/\r?\n$/', '', $piece) ?? $piece;
            $parts[] = $piece;
            if (str_contains($piece, $end)) {
                break;
            }
        }
        return $parts;
    }

    /**
     * @param ?string $textBody
     * @param ?string $htmlBody
     * @param list<array{filename: ?string, mime: string, size: int, contentId: ?string}> $attachments
     */
    private function absorbPart(string $partRaw, ?string &$textBody, ?string &$htmlBody, array &$attachments): void
    {
        [$headerBlock, $body] = $this->splitHeadersBody($partRaw);
        $headers = $this->parseHeaders($headerBlock);
        $contentType = $headers['content-type'] ?? 'text/plain';
        [$mainType, $ctParams] = $this->parseContentType($contentType);
        $encoding = strtolower($headers['content-transfer-encoding'] ?? '7bit');
        $disposition = strtolower($headers['content-disposition'] ?? '');

        if (str_starts_with($mainType, 'multipart/') && isset($ctParams['boundary'])) {
            foreach ($this->splitMultipart($body, $ctParams['boundary']) as $child) {
                $this->absorbPart($child, $textBody, $htmlBody, $attachments);
            }
            return;
        }

        if (str_starts_with($disposition, 'attachment') || str_starts_with($disposition, 'inline')) {
            $filename = $this->extractDispositionFilename($disposition) ?? $ctParams['name'] ?? null;
            $decoded = $this->decodeBody($body, $encoding);
            $contentId = isset($headers['content-id']) ? trim($headers['content-id'], " \t<>") : null;
            $attachments[] = [
                'filename' => $filename,
                'mime' => $mainType,
                'size' => strlen($decoded),
                'contentId' => $contentId,
            ];
            return;
        }

        $decoded = $this->decodeBody($body, $encoding);
        if ($textBody === null && str_starts_with($mainType, 'text/plain')) {
            $textBody = $decoded;
        } elseif ($htmlBody === null && str_starts_with($mainType, 'text/html')) {
            $htmlBody = $decoded;
        }
    }

    private function extractDispositionFilename(string $disposition): ?string
    {
        if (preg_match('/filename\*?=(?:"([^"]+)"|([^;]+))/i', $disposition, $m)) {
            $value = $m[1] !== '' ? $m[1] : $m[2];
            return trim($value);
        }
        return null;
    }

    private function decodeBody(string $body, string $encoding): string
    {
        return match ($encoding) {
            'base64' => base64_decode(preg_replace('/\s+/', '', $body) ?? '', true) ?: '',
            'quoted-printable' => quoted_printable_decode($body),
            default => $body,
        };
    }

    /**
     * @return array<string, string> [email => name]
     */
    private function parseAddressList(string $value): array
    {
        if ($value === '') {
            return [];
        }
        $decoded = $this->decodeMimeHeader($value);
        $result = [];
        foreach ($this->splitAddressList($decoded) as $entry) {
            $entry = trim($entry);
            if ($entry === '') {
                continue;
            }
            if (preg_match('/^(.*)<([^>]+)>\s*$/', $entry, $m)) {
                $name = trim(trim($m[1]), '"');
                $email = trim($m[2]);
                $result[$email] = $name;
            } else {
                $result[$entry] = '';
            }
        }
        return $result;
    }

    /**
     * Split on commas that are not inside quoted strings or angle brackets.
     *
     * @return list<string>
     */
    private function splitAddressList(string $value): array
    {
        $result = [];
        $current = '';
        $inQuote = false;
        $inAngle = false;
        $length = strlen($value);
        for ($i = 0; $i < $length; $i++) {
            $ch = $value[$i];
            if ($ch === '"' && ($i === 0 || $value[$i - 1] !== '\\')) {
                $inQuote = !$inQuote;
            } elseif (!$inQuote && $ch === '<') {
                $inAngle = true;
            } elseif (!$inQuote && $ch === '>') {
                $inAngle = false;
            } elseif (!$inQuote && !$inAngle && $ch === ',') {
                $result[] = $current;
                $current = '';
                continue;
            }
            $current .= $ch;
        }
        if ($current !== '') {
            $result[] = $current;
        }
        return $result;
    }

    /**
     * Decode RFC 2047 encoded-word sequences in a header value.
     */
    private function decodeMimeHeader(string $value): string
    {
        if ($value === '' || !str_contains($value, '=?')) {
            return $value;
        }
        if (function_exists('iconv_mime_decode')) {
            $decoded = @iconv_mime_decode($value, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
            if ($decoded !== false) {
                return $decoded;
            }
        }
        if (function_exists('mb_decode_mimeheader')) {
            return mb_decode_mimeheader($value);
        }
        return $value;
    }
}
