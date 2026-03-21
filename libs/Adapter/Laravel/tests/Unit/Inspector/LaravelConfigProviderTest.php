<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Laravel\Inspector\LaravelConfigProvider;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use PHPUnit\Framework\TestCase;

final class LaravelConfigProviderTest extends TestCase
{
    public function testGetParametersReturnsConfig(): void
    {
        $configData = ['app' => ['name' => 'TestApp'], 'database' => ['default' => 'sqlite']];
        $configRepo = new Repository($configData);

        $app = $this->createAppMock();
        $app->method('make')->willReturnCallback(function (string $abstract) use ($configRepo): mixed {
            if ($abstract === 'config') {
                return $configRepo;
            }
            return null;
        });

        $provider = new LaravelConfigProvider($app);
        $result = $provider->get('params');

        $this->assertArrayHasKey('app', $result);
        $this->assertSame(['name' => 'TestApp'], $result['app']);
    }

    public function testParamsAndParametersAreAliases(): void
    {
        $configRepo = new Repository(['key' => 'value']);

        $app = $this->createAppMock();
        $app->method('make')->willReturnCallback(function (string $abstract) use ($configRepo): mixed {
            if ($abstract === 'config') {
                return $configRepo;
            }
            return null;
        });

        $provider = new LaravelConfigProvider($app);

        $this->assertSame($provider->get('params'), $provider->get('parameters'));
    }

    public function testGetUnknownGroupReturnsEmptyArray(): void
    {
        $app = $this->createAppMock();
        $provider = new LaravelConfigProvider($app);

        $this->assertSame([], $provider->get('nonexistent'));
    }

    public function testGetEventsWithNoDispatcherReturnsEmptyArray(): void
    {
        $app = $this->createAppMock();
        $app->method('bound')->with('events')->willReturn(false);

        $provider = new LaravelConfigProvider($app);

        $this->assertSame([], $provider->get('events'));
    }

    public function testGetServicesWithNoGetBindingsMethodReturnsEmptyArray(): void
    {
        // Application interface doesn't have getBindings - test the method_exists guard
        $app = $this->createAppMock();

        $provider = new LaravelConfigProvider($app);
        $result = $provider->get('di');

        $this->assertSame([], $result);
    }

    public function testGetProvidersWithNoGetLoadedProvidersMethodReturnsEmptyArray(): void
    {
        $app = $this->createAppMock();

        $provider = new LaravelConfigProvider($app);
        $result = $provider->get('providers');

        $this->assertSame([], $result);
    }

    /**
     * @return Application&\PHPUnit\Framework\MockObject\MockObject
     */
    private function createAppMock(): Application
    {
        return $this->createMock(Application::class);
    }
}
