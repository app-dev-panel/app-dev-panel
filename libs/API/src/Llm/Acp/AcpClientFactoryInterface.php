<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Llm\Acp;

/**
 * Factory for creating AcpClient instances with a fresh transport.
 *
 * Each AcpClient holds subprocess state (pipes, process handle) and cannot be reused.
 * This factory creates a new transport+client pair for each ACP chat request.
 */
interface AcpClientFactoryInterface
{
    public function create(): AcpClient;
}
