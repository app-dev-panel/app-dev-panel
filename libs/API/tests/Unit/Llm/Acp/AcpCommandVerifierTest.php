<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Llm\Acp;

use AppDevPanel\Api\Llm\Acp\AcpCommandVerifier;
use PHPUnit\Framework\TestCase;

final class AcpCommandVerifierTest extends TestCase
{
    public function testIsAvailableWithValidCommand(): void
    {
        $verifier = new AcpCommandVerifier();
        // 'php' should be available in test environment
        $this->assertTrue($verifier->isAvailable('php'));
    }

    public function testIsAvailableWithInvalidCommand(): void
    {
        $verifier = new AcpCommandVerifier();
        $this->assertFalse($verifier->isAvailable('nonexistent-command-that-does-not-exist-99999'));
    }
}
