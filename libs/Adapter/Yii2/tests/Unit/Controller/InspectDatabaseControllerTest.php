<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit\Controller;

use AppDevPanel\Adapter\Yii2\Controller\InspectDatabaseController;
use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;
use PHPUnit\Framework\TestCase;
use yii\console\Application;
use yii\console\ExitCode;

final class InspectDatabaseControllerTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        \Yii::$container = new \yii\di\Container();

        $this->basePath = sys_get_temp_dir() . '/adp_inspect_db_test_' . bin2hex(random_bytes(4));
        mkdir($this->basePath, 0o777, true);

        new Application([
            'id' => 'test',
            'basePath' => $this->basePath,
        ]);
    }

    protected function tearDown(): void
    {
        \Yii::$container = new \yii\di\Container();
        \Yii::$app = null;

        if (is_dir($this->basePath)) {
            $items = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->basePath, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($items as $item) {
                $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
            }
            rmdir($this->basePath);
        }
    }

    public function testDefaultActionIsTables(): void
    {
        $controller = $this->createController();

        $this->assertSame('tables', $controller->defaultAction);
    }

    public function testActionTablesWithEmptyResult(): void
    {
        $schemaProvider = $this->createMock(SchemaProviderInterface::class);
        $schemaProvider->expects($this->once())->method('getTables')->willReturn([]);

        $controller = $this->createController($schemaProvider);
        $result = $controller->actionTables();

        $this->assertSame(ExitCode::OK, $result);
    }

    public function testActionTablesWithData(): void
    {
        $tables = [
            ['name' => 'users', 'rows' => '100', 'size' => '16 KB'],
            ['name' => 'posts', 'rows' => '500', 'size' => '64 KB'],
        ];

        $schemaProvider = $this->createMock(SchemaProviderInterface::class);
        $schemaProvider->expects($this->once())->method('getTables')->willReturn($tables);

        $controller = $this->createController($schemaProvider);
        $result = $controller->actionTables();

        $this->assertSame(ExitCode::OK, $result);
    }

    public function testActionTablesJsonOutput(): void
    {
        $tables = [
            ['name' => 'users', 'rows' => '100', 'size' => '16 KB'],
        ];

        $schemaProvider = $this->createMock(SchemaProviderInterface::class);
        $schemaProvider->expects($this->once())->method('getTables')->willReturn($tables);

        $controller = $this->createController($schemaProvider);
        $result = $controller->actionTables(json: true);

        $this->assertSame(ExitCode::OK, $result);
    }

    public function testActionTablesSkipsNonArrayEntries(): void
    {
        $tables = [
            'not-an-array',
            ['name' => 'users', 'rows' => '10', 'size' => '4 KB'],
        ];

        $schemaProvider = $this->createMock(SchemaProviderInterface::class);
        $schemaProvider->method('getTables')->willReturn($tables);

        $controller = $this->createController($schemaProvider);
        $result = $controller->actionTables();

        $this->assertSame(ExitCode::OK, $result);
    }

    public function testActionTablesHandlesMissingFields(): void
    {
        $tables = [
            ['name' => 'users'],
            [],
        ];

        $schemaProvider = $this->createMock(SchemaProviderInterface::class);
        $schemaProvider->method('getTables')->willReturn($tables);

        $controller = $this->createController($schemaProvider);
        $result = $controller->actionTables();

        $this->assertSame(ExitCode::OK, $result);
    }

    public function testActionTableFormattedOutput(): void
    {
        $data = ['columns' => ['id', 'name'], 'rows' => [['1', 'Alice']]];

        $schemaProvider = $this->createMock(SchemaProviderInterface::class);
        $schemaProvider->expects($this->once())->method('getTable')->with('users', 50, 0)->willReturn($data);

        $controller = $this->createController($schemaProvider);
        $result = $controller->actionTable('users');

        $this->assertSame(ExitCode::OK, $result);
    }

    public function testActionTableJsonOutput(): void
    {
        $data = ['columns' => ['id'], 'rows' => [['1']]];

        $schemaProvider = $this->createMock(SchemaProviderInterface::class);
        $schemaProvider->expects($this->once())->method('getTable')->with('orders', 50, 0)->willReturn($data);

        $controller = $this->createController($schemaProvider);
        $result = $controller->actionTable('orders', json: true);

        $this->assertSame(ExitCode::OK, $result);
    }

    public function testActionTableWithCustomLimitAndOffset(): void
    {
        $schemaProvider = $this->createMock(SchemaProviderInterface::class);
        $schemaProvider
            ->expects($this->once())
            ->method('getTable')
            ->with('users', 10, 20)
            ->willReturn([]);

        $controller = $this->createController($schemaProvider);
        $result = $controller->actionTable('users', limit: 10, offset: 20);

        $this->assertSame(ExitCode::OK, $result);
    }

    public function testActionQueryReturnsOk(): void
    {
        $queryResult = [['id' => 1, 'name' => 'Alice']];

        $schemaProvider = $this->createMock(SchemaProviderInterface::class);
        $schemaProvider->expects($this->once())->method('executeQuery')->with('SELECT * FROM users')->willReturn($queryResult);

        $controller = $this->createController($schemaProvider);
        $result = $controller->actionQuery('SELECT * FROM users');

        $this->assertSame(ExitCode::OK, $result);
    }

    public function testActionQueryWithEmptySql(): void
    {
        $schemaProvider = $this->createMock(SchemaProviderInterface::class);
        $schemaProvider->expects($this->never())->method('executeQuery');

        $controller = $this->createController($schemaProvider);
        $result = $controller->actionQuery('');

        $this->assertSame(ExitCode::UNSPECIFIED_ERROR, $result);
    }

    public function testActionQueryHandlesException(): void
    {
        $schemaProvider = $this->createMock(SchemaProviderInterface::class);
        $schemaProvider
            ->method('executeQuery')
            ->willThrowException(new \RuntimeException('Syntax error'));

        $controller = $this->createController($schemaProvider);
        $result = $controller->actionQuery('INVALID SQL');

        $this->assertSame(ExitCode::UNSPECIFIED_ERROR, $result);
    }

    public function testActionExplainReturnsOk(): void
    {
        $explainResult = [['type' => 'ALL', 'table' => 'users', 'rows' => 100]];

        $schemaProvider = $this->createMock(SchemaProviderInterface::class);
        $schemaProvider
            ->expects($this->once())
            ->method('explainQuery')
            ->with('SELECT * FROM users', [], false)
            ->willReturn($explainResult);

        $controller = $this->createController($schemaProvider);
        $result = $controller->actionExplain('SELECT * FROM users');

        $this->assertSame(ExitCode::OK, $result);
    }

    public function testActionExplainWithAnalyze(): void
    {
        $schemaProvider = $this->createMock(SchemaProviderInterface::class);
        $schemaProvider
            ->expects($this->once())
            ->method('explainQuery')
            ->with('SELECT 1', [], true)
            ->willReturn([]);

        $controller = $this->createController($schemaProvider);
        $result = $controller->actionExplain('SELECT 1', analyze: true);

        $this->assertSame(ExitCode::OK, $result);
    }

    public function testActionExplainWithEmptySql(): void
    {
        $schemaProvider = $this->createMock(SchemaProviderInterface::class);
        $schemaProvider->expects($this->never())->method('explainQuery');

        $controller = $this->createController($schemaProvider);
        $result = $controller->actionExplain('');

        $this->assertSame(ExitCode::UNSPECIFIED_ERROR, $result);
    }

    public function testActionExplainHandlesException(): void
    {
        $schemaProvider = $this->createMock(SchemaProviderInterface::class);
        $schemaProvider
            ->method('explainQuery')
            ->willThrowException(new \RuntimeException('Query failed'));

        $controller = $this->createController($schemaProvider);
        $result = $controller->actionExplain('BAD QUERY');

        $this->assertSame(ExitCode::UNSPECIFIED_ERROR, $result);
    }

    public function testActionTablesViaRunAction(): void
    {
        $schemaProvider = $this->createMock(SchemaProviderInterface::class);
        $schemaProvider->method('getTables')->willReturn([]);

        $controller = $this->createController($schemaProvider);
        $result = $controller->runAction('tables');

        $this->assertSame(ExitCode::OK, $result);
    }

    public function testActionQueryJsonOutput(): void
    {
        $schemaProvider = $this->createMock(SchemaProviderInterface::class);
        $schemaProvider->method('executeQuery')->willReturn([['id' => 1]]);

        $controller = $this->createController($schemaProvider);
        $result = $controller->actionQuery('SELECT 1', json: false);

        $this->assertSame(ExitCode::OK, $result);
    }

    public function testActionExplainJsonOutput(): void
    {
        $schemaProvider = $this->createMock(SchemaProviderInterface::class);
        $schemaProvider->method('explainQuery')->willReturn([['plan' => 'Seq Scan']]);

        $controller = $this->createController($schemaProvider);
        $result = $controller->actionExplain('SELECT 1', json: true);

        $this->assertSame(ExitCode::OK, $result);
    }

    private function createController(?SchemaProviderInterface $schemaProvider = null): InspectDatabaseController
    {
        return new InspectDatabaseController(
            'inspect-database',
            \Yii::$app,
            $schemaProvider ?? $this->createMock(SchemaProviderInterface::class),
        );
    }
}
