<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\Routing;

use AppDevPanel\Adapter\Symfony\Routing\AdpRoutingLoaderDecorator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

final class AdpRoutingLoaderDecoratorTest extends TestCase
{
    public function testAppendsAdpRoutesToInnerCollection(): void
    {
        $userCollection = new RouteCollection();
        $userCollection->add('home', new Route('/'));

        $adpCollection = new RouteCollection();
        $adpCollection->add('adp_assets', new Route('/debug-assets/{path}'));
        $adpCollection->add('adp_panel_root', new Route('/debug'));

        $adpPath = $this->makeTempFile();
        $inner = $this->createInnerLoader($userCollection, $adpPath, $adpCollection);

        $decorator = new AdpRoutingLoaderDecorator($inner, $adpPath);
        $result = $decorator->load('user-resource', null);

        $this->assertInstanceOf(RouteCollection::class, $result);
        $this->assertNotNull($result->get('home'));
        $this->assertNotNull($result->get('adp_assets'));
        $this->assertNotNull($result->get('adp_panel_root'));
    }

    public function testReturnsInnerResultUnchangedWhenRoutesFileMissing(): void
    {
        $userCollection = new RouteCollection();
        $userCollection->add('home', new Route('/'));

        $inner = $this->createInnerLoader($userCollection, '/nope', new RouteCollection());

        $decorator = new AdpRoutingLoaderDecorator($inner, '/definitely/missing/adp.php');
        $result = $decorator->load('user-resource', null);

        $this->assertCount(1, $result);
        $this->assertNotNull($result->get('home'));
    }

    public function testSupportsDelegatesToInner(): void
    {
        $inner = $this->createMock(LoaderInterface::class);
        $inner->expects($this->once())->method('supports')->with('foo', 'php')->willReturn(true);

        $decorator = new AdpRoutingLoaderDecorator($inner, '/irrelevant.php');
        $this->assertTrue($decorator->supports('foo', 'php'));
    }

    private function makeTempFile(): string
    {
        $path = sys_get_temp_dir() . '/adp-routes-' . uniqid() . '.php';
        file_put_contents($path, "<?php\nreturn static function (): void {};\n");
        return $path;
    }

    private function createInnerLoader(
        RouteCollection $userCollection,
        string $adpPath,
        RouteCollection $adpCollection,
    ): LoaderInterface {
        $adpLoader = $this->createMock(LoaderInterface::class);
        $adpLoader->method('load')->with($adpPath)->willReturn($adpCollection);

        $resolver = $this->createMock(LoaderResolverInterface::class);
        $resolver->method('resolve')->willReturnCallback(static fn(mixed $resource) => $resource === $adpPath
            ? $adpLoader
            : false);

        $inner = $this->createMock(LoaderInterface::class);
        $inner->method('load')->willReturn($userCollection);
        $inner->method('getResolver')->willReturn($resolver);

        return $inner;
    }
}
