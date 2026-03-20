<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yiisoft\Tests\Unit\Collector\Validator;

use AppDevPanel\Adapter\Yiisoft\Collector\Validator\ValidatorInterfaceProxy;
use AppDevPanel\Kernel\Collector\ValidatorCollector;
use PHPUnit\Framework\TestCase;

final class ValidatorInterfaceProxyTest extends TestCase
{
    protected function setUp(): void
    {
        if (!interface_exists(\Yiisoft\Validator\ValidatorInterface::class, true)) {
            $this->markTestSkipped('yiisoft/validator is not installed.');
        }
    }

    public function testValidateDelegatesToDecoratedAndCollects(): void
    {
        $result = new \Yiisoft\Validator\Result();
        $rules = [new \Yiisoft\Validator\Rule\Required()];

        $validator = $this->createMock(\Yiisoft\Validator\ValidatorInterface::class);
        $validator->expects($this->once())->method('validate')->with('test-data', $rules, null)->willReturn($result);

        $collector = new ValidatorCollector();
        $collector->startup();

        $proxy = new ValidatorInterfaceProxy($validator, $collector);
        $returnedResult = $proxy->validate('test-data', $rules);

        $this->assertSame($result, $returnedResult);

        $collected = $collector->getCollected();
        $this->assertCount(1, $collected);
        $this->assertSame('test-data', $collected[0]['value']);
        $this->assertTrue($collected[0]['result']);
        $this->assertSame([], $collected[0]['errors']);
        $this->assertSame($rules, $collected[0]['rules']);
    }

    public function testValidateWithErrors(): void
    {
        $result = new \Yiisoft\Validator\Result();
        $result->addError('Field is required.');

        $validator = $this->createMock(\Yiisoft\Validator\ValidatorInterface::class);
        $validator->method('validate')->willReturn($result);

        $collector = new ValidatorCollector();
        $collector->startup();

        $proxy = new ValidatorInterfaceProxy($validator, $collector);
        $proxy->validate('', [new \Yiisoft\Validator\Rule\Required()]);

        $collected = $collector->getCollected();
        $this->assertCount(1, $collected);
        $this->assertFalse($collected[0]['result']);
        $this->assertNotEmpty($collected[0]['errors']);
    }

    public function testValidateWithNullRulesAndRulesProvider(): void
    {
        $rules = [new \Yiisoft\Validator\Rule\Required()];
        $data = new class($rules) implements \Yiisoft\Validator\RulesProviderInterface {
            public function __construct(
                private array $rules,
            ) {}

            public function getRules(): iterable
            {
                return $this->rules;
            }
        };

        $result = new \Yiisoft\Validator\Result();
        $validator = $this->createMock(\Yiisoft\Validator\ValidatorInterface::class);
        $validator->method('validate')->willReturn($result);

        $collector = new ValidatorCollector();
        $collector->startup();

        $proxy = new ValidatorInterfaceProxy($validator, $collector);
        $proxy->validate($data);

        $collected = $collector->getCollected();
        $this->assertCount(1, $collected);
        $this->assertSame($data, $collected[0]['value']);
        $this->assertSame($rules, $collected[0]['rules']);
    }

    public function testValidateWithTraversableRules(): void
    {
        $rulesArray = [new \Yiisoft\Validator\Rule\Required()];
        $rules = new \ArrayIterator($rulesArray);

        $result = new \Yiisoft\Validator\Result();
        $validator = $this->createMock(\Yiisoft\Validator\ValidatorInterface::class);
        $validator->method('validate')->willReturn($result);

        $collector = new ValidatorCollector();
        $collector->startup();

        $proxy = new ValidatorInterfaceProxy($validator, $collector);
        $proxy->validate('data', $rules);

        $collected = $collector->getCollected();
        $this->assertCount(1, $collected);
        $this->assertSame($rulesArray, $collected[0]['rules']);
    }

    public function testValidatePassesContextToDecorated(): void
    {
        $context = new \Yiisoft\Validator\ValidationContext();
        $result = new \Yiisoft\Validator\Result();

        $validator = $this->createMock(\Yiisoft\Validator\ValidatorInterface::class);
        $validator->expects($this->once())->method('validate')->with('data', null, $context)->willReturn($result);

        $collector = new ValidatorCollector();
        $collector->startup();

        $proxy = new ValidatorInterfaceProxy($validator, $collector);
        $proxy->validate('data', null, $context);
    }

    public function testValidateUpdatesSummary(): void
    {
        $result = new \Yiisoft\Validator\Result();
        $validator = $this->createMock(\Yiisoft\Validator\ValidatorInterface::class);
        $validator->method('validate')->willReturn($result);

        $collector = new ValidatorCollector();
        $collector->startup();

        $proxy = new ValidatorInterfaceProxy($validator, $collector);
        $proxy->validate('data', [new \Yiisoft\Validator\Rule\Required()]);

        $this->assertSame(['validator' => ['total' => 1, 'valid' => 1, 'invalid' => 0]], $collector->getSummary());
    }
}
