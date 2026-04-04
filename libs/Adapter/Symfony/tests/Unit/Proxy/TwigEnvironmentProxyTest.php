<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\Proxy;

use AppDevPanel\Adapter\Symfony\Proxy\TwigEnvironmentProxy;
use AppDevPanel\Kernel\Collector\TemplateCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

final class TwigEnvironmentProxyTest extends TestCase
{
    private TemplateCollector $collector;

    protected function setUp(): void
    {
        $this->collector = new TemplateCollector(new TimelineCollector());
        $this->collector->startup();
    }

    public function testRenderCollectsTemplateData(): void
    {
        $inner = $this->createMock(Environment::class);
        $inner->method('render')->with('page/home.html.twig', [])->willReturn('<h1>Home</h1>');

        $proxy = new TwigEnvironmentProxy($inner, $this->collector);

        $result = $proxy->render('page/home.html.twig');

        $this->assertSame('<h1>Home</h1>', $result);

        $collected = $this->collector->getCollected();
        $this->assertSame(1, $collected['renderCount']);
        $this->assertSame('page/home.html.twig', $collected['renders'][0]['template']);
        $this->assertSame('<h1>Home</h1>', $collected['renders'][0]['output']);
        $this->assertGreaterThan(0, $collected['renders'][0]['renderTime']);
    }

    public function testRenderWithContextCollectsParameters(): void
    {
        $context = ['title' => 'Home', 'user' => 'admin'];

        $inner = $this->createMock(Environment::class);
        $inner->method('render')->with('page/home.html.twig', $context)->willReturn('<h1>Home</h1>');

        $proxy = new TwigEnvironmentProxy($inner, $this->collector);

        $proxy->render('page/home.html.twig', $context);

        $collected = $this->collector->getCollected();
        $this->assertSame($context, $collected['renders'][0]['parameters']);
    }

    public function testMultipleRendersCollected(): void
    {
        $inner = $this->createMock(Environment::class);
        $inner->method('render')->willReturn('<html></html>');

        $proxy = new TwigEnvironmentProxy($inner, $this->collector);

        $proxy->render('page/home.html.twig');
        $proxy->render('page/contact.html.twig');
        $proxy->render('page/users.html.twig');

        $collected = $this->collector->getCollected();
        $this->assertSame(3, $collected['renderCount']);
        $this->assertSame('page/home.html.twig', $collected['renders'][0]['template']);
        $this->assertSame('page/contact.html.twig', $collected['renders'][1]['template']);
        $this->assertSame('page/users.html.twig', $collected['renders'][2]['template']);
    }

    public function testMagicCallDelegatesToDecorated(): void
    {
        $inner = $this->createMock(Environment::class);
        $inner->expects($this->once())->method('getCharset')->willReturn('UTF-8');

        $proxy = new TwigEnvironmentProxy($inner, $this->collector);

        $this->assertSame('UTF-8', $proxy->getCharset());
    }
}
