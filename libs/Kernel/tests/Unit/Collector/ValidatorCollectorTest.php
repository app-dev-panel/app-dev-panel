<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\ValidatorCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;

final class ValidatorCollectorTest extends AbstractCollectorTestCase
{
    protected function getCollector(): CollectorInterface
    {
        return new ValidatorCollector();
    }

    protected function collectTestData(CollectorInterface $collector): void
    {
        assert($collector instanceof ValidatorCollector, 'Expected ValidatorCollector instance');
        $collector->collect('valid@email.com', true, [], ['email', 'required']);
        $collector->collect('', false, [['message' => 'Value cannot be blank', 'valuePath' => []]], ['required']);
        $collector->collect(42, true, []);
    }

    protected function checkCollectedData(array $data): void
    {
        parent::checkCollectedData($data);

        $this->assertCount(3, $data);
        $this->assertTrue($data[0]['result']);
        $this->assertFalse($data[1]['result']);
        $this->assertNotEmpty($data[1]['errors']);
        $this->assertTrue($data[2]['result']);
    }

    protected function checkSummaryData(array $data): void
    {
        parent::checkSummaryData($data);

        $this->assertArrayHasKey('validator', $data);
        $this->assertSame(3, $data['validator']['total']);
        $this->assertSame(2, $data['validator']['valid']);
        $this->assertSame(1, $data['validator']['invalid']);
    }
}
