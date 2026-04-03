<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Tests\Unit\EventListener;

use AppDevPanel\Adapter\Laravel\EventListener\ValidatorListener;
use AppDevPanel\Kernel\Collector\ValidatorCollector;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as ValidationFactory;
use PHPUnit\Framework\TestCase;

final class ValidatorListenerTest extends TestCase
{
    public function testPassingValidationIsCollected(): void
    {
        [$collector, $factory] = $this->createSetup();

        $validator = $factory->make(['email' => 'user@example.com', 'name' => 'John'], [
            'email' => 'required|email',
            'name' => 'required|string|min:2',
        ]);
        $validator->passes();

        $collected = $collector->getCollected();
        $this->assertCount(1, $collected);
        $this->assertTrue($collected[0]['result']);
        $this->assertSame(['email' => 'user@example.com', 'name' => 'John'], $collected[0]['value']);
    }

    public function testFailingValidationIsCollected(): void
    {
        [$collector, $factory] = $this->createSetup();

        $validator = $factory->make(['email' => 'not-an-email', 'name' => ''], [
            'email' => 'required|email',
            'name' => 'required|string|min:2',
        ]);
        $validator->fails();

        $collected = $collector->getCollected();
        $this->assertCount(1, $collected);
        $this->assertFalse($collected[0]['result']);
        $this->assertNotEmpty($collected[0]['errors']);
    }

    public function testRulesAreCaptured(): void
    {
        [$collector, $factory] = $this->createSetup();

        $validator = $factory->make(['age' => 25], ['age' => 'required|integer|min:18']);
        $validator->passes();

        $collected = $collector->getCollected();
        $this->assertArrayHasKey('age', $collected[0]['rules']);
    }

    public function testMultipleValidationsAreCollected(): void
    {
        [$collector, $factory] = $this->createSetup();

        $factory->make(['x' => 1], ['x' => 'required'])->passes();
        $factory->make(['y' => ''], ['y' => 'required'])->fails();

        $collected = $collector->getCollected();
        $this->assertCount(2, $collected);
        $this->assertTrue($collected[0]['result']);
        $this->assertFalse($collected[1]['result']);
    }

    /**
     * @return array{ValidatorCollector, ValidationFactory}
     */
    private function createSetup(): array
    {
        $collector = new ValidatorCollector();
        $collector->startup();

        $translator = new Translator(new ArrayLoader(), 'en');
        $factory = new ValidationFactory($translator);

        $listener = new ValidatorListener(static fn() => $collector);
        $listener->register($factory);

        return [$collector, $factory];
    }
}
