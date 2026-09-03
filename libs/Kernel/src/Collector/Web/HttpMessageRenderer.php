<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector\Web;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Renders a PSR-7 message as raw HTTP text (start line, headers, body) for
 * the request panel — like `GuzzleHttp\Psr7\Message::toString()`, but safe
 * for debug tooling: binary bodies (images, fonts, archives, octet streams —
 * i.e. served assets) are never read, and the body is read through
 * {@see StreamBodyReader} (size cap, seekability, exception safety).
 */
final class HttpMessageRenderer
{
    /**
     * Content types whose bodies are captured verbatim.
     */
    private const string TEXTUAL_CONTENT_TYPE_PATTERN = '~^(text/|application/(json|xml|x-www-form-urlencoded|javascript|ecmascript|ld\+json|problem\+json|x-ndjson|graphql|soap\+xml|xhtml\+xml|atom\+xml|rss\+xml)|multipart/form-data|[^;]+\+(json|xml))~i';

    private readonly StreamBodyReader $bodyReader;

    public function __construct(int $maxBodySize)
    {
        $this->bodyReader = new StreamBodyReader($maxBodySize);
    }

    public function render(MessageInterface $message): string
    {
        try {
            $raw = $this->startLine($message);
            foreach ($message->getHeaders() as $name => $values) {
                $raw .= "\r\n" . $name . ': ' . implode(', ', $values);
            }

            return $raw . "\r\n\r\n" . $this->body($message);
        } catch (\Throwable $e) {
            return sprintf('[message unavailable: %s]', $e->getMessage());
        }
    }

    private function startLine(MessageInterface $message): string
    {
        if ($message instanceof ResponseInterface) {
            return 'HTTP/'
            . $message->getProtocolVersion()
            . ' '
            . $message->getStatusCode()
            . ' '
            . $message->getReasonPhrase();
        }

        if (!$message instanceof RequestInterface) {
            return '';
        }

        $line =
            trim($message->getMethod() . ' ' . $message->getRequestTarget())
            . ' HTTP/'
            . $message->getProtocolVersion();
        if (!$message->hasHeader('host')) {
            $line .= "\r\nHost: " . $message->getUri()->getHost();
        }

        return $line;
    }

    private function body(MessageInterface $message): string
    {
        $contentType = $message->getHeaderLine('Content-Type');
        if ($contentType !== '' && preg_match(self::TEXTUAL_CONTENT_TYPE_PATTERN, $contentType) !== 1) {
            $length = $message->getHeaderLine('Content-Length');

            return sprintf(
                '[binary body omitted: %s, %s]',
                $contentType,
                $length === '' ? 'unknown size' : $length . ' bytes',
            );
        }

        return $this->bodyReader->read($message->getBody());
    }
}
