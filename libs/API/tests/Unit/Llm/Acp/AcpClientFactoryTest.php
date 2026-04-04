<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Llm\Acp;

use AppDevPanel\Api\Llm\Acp\AcpClient;
use AppDevPanel\Api\Llm\Acp\AcpClientFactory;
use PHPUnit\Framework\TestCase;

final class AcpClientFactoryTest extends TestCase
{
    public function testCreateReturnsAcpClient(): void
    {
        $factory = new AcpClientFactory();
        $client = $factory->create();

        $this->assertInstanceOf(AcpClient::class, $client);
    }

    public function testCreateReturnsFreshInstances(): void
    {
        $factory = new AcpClientFactory();
        $client1 = $factory->create();
        $client2 = $factory->create();

        $this->assertNotSame($client1, $client2);
    }
}
