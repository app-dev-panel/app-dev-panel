<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Llm\Acp;

use AppDevPanel\Api\Llm\Acp\AcpCommandAllowlist;
use PHPUnit\Framework\TestCase;

final class AcpCommandAllowlistTest extends TestCase
{
    public function testDefaultAllowsKnownAgents(): void
    {
        $allowlist = new AcpCommandAllowlist();

        $this->assertTrue($allowlist->isAllowed('npx'));
        $this->assertTrue($allowlist->isAllowed('claude'));
        $this->assertTrue($allowlist->isAllowed('gemini'));
        $this->assertTrue($allowlist->isAllowed('node'));
    }

    public function testDefaultRejectsArbitraryBinaries(): void
    {
        $allowlist = new AcpCommandAllowlist();

        $this->assertFalse($allowlist->isAllowed('sh'));
        $this->assertFalse($allowlist->isAllowed('bash'));
        $this->assertFalse($allowlist->isAllowed('curl'));
        $this->assertFalse($allowlist->isAllowed('python'));
        $this->assertFalse($allowlist->isAllowed('/bin/sh'));
    }

    public function testRejectsEmptyString(): void
    {
        $this->assertFalse(new AcpCommandAllowlist()->isAllowed(''));
    }

    public function testAcceptsAbsolutePathToAllowedBinary(): void
    {
        $allowlist = new AcpCommandAllowlist();

        $this->assertTrue($allowlist->isAllowed('/usr/local/bin/npx'));
        $this->assertTrue($allowlist->isAllowed('/opt/homebrew/bin/claude'));
    }

    public function testCustomAllowlistOverridesDefaults(): void
    {
        $allowlist = new AcpCommandAllowlist(['my-agent']);

        $this->assertTrue($allowlist->isAllowed('my-agent'));
        $this->assertFalse($allowlist->isAllowed('npx'));
    }

    public function testEmptyCustomListRejectsEverything(): void
    {
        $allowlist = new AcpCommandAllowlist([]);

        $this->assertFalse($allowlist->isAllowed('npx'));
    }

    public function testGetCommandsReturnsConfiguredList(): void
    {
        $allowlist = new AcpCommandAllowlist(['a', 'b']);

        $this->assertSame(['a', 'b'], $allowlist->getCommands());
    }

    public function testGetCommandsReturnsDefaultsWhenNotConfigured(): void
    {
        $allowlist = new AcpCommandAllowlist();

        $this->assertSame(AcpCommandAllowlist::DEFAULT_COMMANDS, $allowlist->getCommands());
    }
}
