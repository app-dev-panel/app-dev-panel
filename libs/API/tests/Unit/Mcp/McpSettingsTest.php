<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Mcp;

use AppDevPanel\Api\Mcp\McpSettings;
use PHPUnit\Framework\TestCase;

final class McpSettingsTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/adp-mcp-settings-test-' . uniqid();
        mkdir($this->tmpDir, 0o755, true);
    }

    protected function tearDown(): void
    {
        @unlink($this->tmpDir . '/mcp-settings.json');
        @rmdir($this->tmpDir);
    }

    public function testDefaultsToEnabled(): void
    {
        $settings = new McpSettings($this->tmpDir);

        $this->assertTrue($settings->isEnabled());
    }

    public function testSetEnabledFalse(): void
    {
        $settings = new McpSettings($this->tmpDir);
        $settings->setEnabled(false);

        $this->assertFalse($settings->isEnabled());
    }

    public function testSetEnabledTrue(): void
    {
        $settings = new McpSettings($this->tmpDir);
        $settings->setEnabled(false);
        $settings->setEnabled(true);

        $this->assertTrue($settings->isEnabled());
    }

    public function testPersistsToDisk(): void
    {
        $settings = new McpSettings($this->tmpDir);
        $settings->setEnabled(false);

        // Create a new instance reading from disk
        $settings2 = new McpSettings($this->tmpDir);
        $this->assertFalse($settings2->isEnabled());
    }

    public function testCreatesDirectoryIfNotExists(): void
    {
        $deepDir = $this->tmpDir . '/nested/dir';
        $settings = new McpSettings($deepDir);
        $settings->setEnabled(false);

        $this->assertDirectoryExists($deepDir);
        $this->assertFalse($settings->isEnabled());

        @unlink($deepDir . '/mcp-settings.json');
        @rmdir($deepDir);
        @rmdir($this->tmpDir . '/nested');
    }
}
