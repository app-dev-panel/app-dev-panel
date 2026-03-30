<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit\EventListener;

use AppDevPanel\Adapter\Yii2\EventListener\AuthorizationListener;
use AppDevPanel\Kernel\Collector\AuthorizationCollector;
use PHPUnit\Framework\TestCase;
use yii\web\User;
use yii\web\UserEvent;

final class AuthorizationListenerTest extends TestCase
{
    public function testRegisterDoesNotThrow(): void
    {
        $collector = new AuthorizationCollector();
        $collector->startup();

        $listener = new AuthorizationListener($collector);
        $listener->register();

        // If we got here without exception, event binding succeeded
        $this->assertTrue(true);
    }

    public function testOnAfterLoginCollectsUser(): void
    {
        $collector = new AuthorizationCollector();
        $collector->startup();

        $listener = new AuthorizationListener($collector);

        $identity = $this->createMock(\yii\web\IdentityInterface::class);
        $identity->method('getId')->willReturn(42);

        $event = new UserEvent();
        $event->identity = $identity;
        $event->duration = 3600;
        $event->cookieBased = false;

        $listener->onAfterLogin($event);

        $data = $collector->getCollected();
        $this->assertSame('42', $data['username']);
        $this->assertTrue($data['authenticated']);
        $this->assertCount(1, $data['authenticationEvents']);
        $this->assertSame('login', $data['authenticationEvents'][0]['type']);
        $this->assertSame('success', $data['authenticationEvents'][0]['result']);
    }

    public function testOnAfterLogoutCollectsEvent(): void
    {
        $collector = new AuthorizationCollector();
        $collector->startup();

        $listener = new AuthorizationListener($collector);

        $identity = $this->createMock(\yii\web\IdentityInterface::class);
        $identity->method('getId')->willReturn(42);

        $event = new UserEvent();
        $event->identity = $identity;

        $listener->onAfterLogout($event);

        $data = $collector->getCollected();
        $this->assertCount(1, $data['authenticationEvents']);
        $this->assertSame('logout', $data['authenticationEvents'][0]['type']);
        $this->assertSame('success', $data['authenticationEvents'][0]['result']);
        $this->assertSame('42', $data['authenticationEvents'][0]['details']['user']);
    }

    public function testOnAfterLoginSkipsWhenNoIdentity(): void
    {
        $collector = new AuthorizationCollector();
        $collector->startup();

        $listener = new AuthorizationListener($collector);

        $event = new UserEvent();
        $event->identity = null;

        $listener->onAfterLogin($event);

        $data = $collector->getCollected();
        $this->assertNull($data['username']);
        $this->assertCount(0, $data['authenticationEvents']);
    }

    protected function tearDown(): void
    {
        // Clean up static event bindings
        \yii\base\Event::offAll();
    }
}
