<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\Proxy;

use AppDevPanel\Adapter\Symfony\Proxy\SymfonyValidatorProxy;
use AppDevPanel\Kernel\Collector\ValidatorCollector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ContextualValidatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class SymfonyValidatorProxyTest extends TestCase
{
    private ValidatorCollector $collector;

    protected function setUp(): void
    {
        $this->collector = new ValidatorCollector();
        $this->collector->startup();
    }

    public function testValidateCollectsPassingValidation(): void
    {
        $violations = new ConstraintViolationList();

        $inner = $this->createMock(ValidatorInterface::class);
        $inner->method('validate')->willReturn($violations);

        $proxy = new SymfonyValidatorProxy($inner, $this->collector);

        $result = $proxy->validate(['email' => 'user@example.com']);

        $this->assertSame($violations, $result);

        $collected = $this->collector->getCollected();
        $this->assertCount(1, $collected);
        $this->assertTrue($collected[0]['result']);
        $this->assertEmpty($collected[0]['errors']);
    }

    public function testValidateCollectsFailingValidation(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('Invalid email', '', [], null, '[email]', 'not-an-email'),
            new ConstraintViolation('Name is required', '', [], null, '[name]', ''),
        ]);

        $inner = $this->createMock(ValidatorInterface::class);
        $inner->method('validate')->willReturn($violations);

        $proxy = new SymfonyValidatorProxy($inner, $this->collector);

        $result = $proxy->validate(['email' => 'not-an-email', 'name' => '']);

        $this->assertSame(2, $result->count());

        $collected = $this->collector->getCollected();
        $this->assertFalse($collected[0]['result']);
        $this->assertArrayHasKey('[email]', $collected[0]['errors']);
        $this->assertArrayHasKey('[name]', $collected[0]['errors']);
    }

    public function testValidatePropertyDelegatesToDecorated(): void
    {
        $violations = new ConstraintViolationList();

        $inner = $this->createMock(ValidatorInterface::class);
        $inner->expects($this->once())->method('validateProperty')->willReturn($violations);

        $proxy = new SymfonyValidatorProxy($inner, $this->collector);

        $obj = new \stdClass();
        $result = $proxy->validateProperty($obj, 'name');

        $this->assertSame($violations, $result);
    }

    public function testStartContextDelegatesToDecorated(): void
    {
        $contextualValidator = $this->createMock(ContextualValidatorInterface::class);

        $inner = $this->createMock(ValidatorInterface::class);
        $inner->expects($this->once())->method('startContext')->willReturn($contextualValidator);

        $proxy = new SymfonyValidatorProxy($inner, $this->collector);

        $this->assertSame($contextualValidator, $proxy->startContext());
    }
}
