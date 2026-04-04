<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Llm\Acp;

/**
 * Default factory: creates AcpClient backed by a real AcpTransport (proc_open subprocess).
 */
final class AcpClientFactory implements AcpClientFactoryInterface
{
    public function create(): AcpClient
    {
        return new AcpClient(new AcpTransport());
    }
}
