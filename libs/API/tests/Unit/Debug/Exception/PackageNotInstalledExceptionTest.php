<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Debug\Exception;

use AppDevPanel\Api\Debug\Exception\PackageNotInstalledException;
use PHPUnit\Framework\TestCase;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

final class PackageNotInstalledExceptionTest extends TestCase
{
    public function testGetName(): void
    {
        $exception = new PackageNotInstalledException('vendor/package');
        $this->assertSame('Package "vendor/package" is not installed.', $exception->getName());
    }

    public function testGetSolution(): void
    {
        $exception = new PackageNotInstalledException('vendor/package');
        $solution = $exception->getSolution();

        $this->assertStringContainsString('composer require vendor/package', $solution);
        $this->assertStringContainsString('vendor/package', $solution);
    }

    public function testImplementsFriendlyException(): void
    {
        $exception = new PackageNotInstalledException('vendor/package');
        $this->assertInstanceOf(FriendlyExceptionInterface::class, $exception);
    }

    public function testCustomMessage(): void
    {
        $exception = new PackageNotInstalledException('vendor/package', 'custom message', 500);
        $this->assertSame('custom message', $exception->getMessage());
        $this->assertSame(500, $exception->getCode());
    }
}
