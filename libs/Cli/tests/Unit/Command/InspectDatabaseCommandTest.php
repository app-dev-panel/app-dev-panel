<?php

declare(strict_types=1);

namespace Unit\Command;

use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;
use AppDevPanel\Cli\Command\InspectDatabaseCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class InspectDatabaseCommandTest extends TestCase
{
    public function testListTablesEmpty(): void
    {
        $provider = $this->createMock(SchemaProviderInterface::class);
        $provider->method('getTables')->willReturn([]);

        $tester = new CommandTester(new InspectDatabaseCommand($provider));
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('No tables found', $tester->getDisplay());
    }

    public function testListTablesTable(): void
    {
        $provider = $this->createMock(SchemaProviderInterface::class);
        $provider
            ->method('getTables')
            ->willReturn([
                ['name' => 'users', 'rows' => '42', 'size' => '16KB'],
                ['name' => 'posts', 'rows' => '100', 'size' => '64KB'],
            ]);

        $tester = new CommandTester(new InspectDatabaseCommand($provider));
        $tester->execute(['action' => 'tables']);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('users', $display);
        $this->assertStringContainsString('posts', $display);
        $this->assertStringContainsString('42', $display);
    }

    public function testListTablesJson(): void
    {
        $tables = [['name' => 'users', 'rows' => '10', 'size' => '4KB']];
        $provider = $this->createMock(SchemaProviderInterface::class);
        $provider->method('getTables')->willReturn($tables);

        $tester = new CommandTester(new InspectDatabaseCommand($provider));
        $tester->execute(['action' => 'tables', '--json' => true]);

        $this->assertSame(0, $tester->getStatusCode());
        $decoded = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('users', $decoded[0]['name']);
    }

    public function testViewTableRequiresName(): void
    {
        $provider = $this->createMock(SchemaProviderInterface::class);

        $tester = new CommandTester(new InspectDatabaseCommand($provider));
        $tester->execute(['action' => 'table']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Table name is required', $tester->getDisplay());
    }

    public function testViewTable(): void
    {
        $provider = $this->createMock(SchemaProviderInterface::class);
        $provider
            ->method('getTable')
            ->with('users', 50, 0)
            ->willReturn([
                'columns' => [
                    [
                        'name' => 'id',
                        'type' => 'int',
                        'allowNull' => false,
                        'defaultValue' => null,
                        'isPrimaryKey' => true,
                    ],
                    [
                        'name' => 'email',
                        'type' => 'varchar',
                        'allowNull' => true,
                        'defaultValue' => null,
                        'isPrimaryKey' => false,
                    ],
                ],
                'records' => [['id' => 1, 'email' => 'test@example.com']],
            ]);

        $tester = new CommandTester(new InspectDatabaseCommand($provider));
        $tester->execute(['action' => 'table', 'target' => 'users']);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Table: users', $display);
        $this->assertStringContainsString('id', $display);
        $this->assertStringContainsString('email', $display);
    }

    public function testViewTableJson(): void
    {
        $tableData = ['columns' => [], 'records' => []];
        $provider = $this->createMock(SchemaProviderInterface::class);
        $provider->method('getTable')->willReturn($tableData);

        $tester = new CommandTester(new InspectDatabaseCommand($provider));
        $tester->execute(['action' => 'table', 'target' => 'users', '--json' => true]);

        $this->assertSame(0, $tester->getStatusCode());
        $decoded = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('columns', $decoded);
    }

    public function testQueryRequiresSql(): void
    {
        $provider = $this->createMock(SchemaProviderInterface::class);

        $tester = new CommandTester(new InspectDatabaseCommand($provider));
        $tester->execute(['action' => 'query']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('SQL query is required', $tester->getDisplay());
    }

    public function testQueryExecutes(): void
    {
        $provider = $this->createMock(SchemaProviderInterface::class);
        $provider
            ->method('executeQuery')
            ->with('SELECT 1')
            ->willReturn(['rows' => [['1' => '1']], 'count' => 1]);

        $tester = new CommandTester(new InspectDatabaseCommand($provider));
        $tester->execute(['action' => 'query', 'target' => 'SELECT 1']);

        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testQueryError(): void
    {
        $provider = $this->createMock(SchemaProviderInterface::class);
        $provider->method('executeQuery')->willThrowException(new \RuntimeException('Syntax error'));

        $tester = new CommandTester(new InspectDatabaseCommand($provider));
        $tester->execute(['action' => 'query', 'target' => 'INVALID SQL']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Syntax error', $tester->getDisplay());
    }

    public function testExplainRequiresSql(): void
    {
        $provider = $this->createMock(SchemaProviderInterface::class);

        $tester = new CommandTester(new InspectDatabaseCommand($provider));
        $tester->execute(['action' => 'explain']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('SQL query is required', $tester->getDisplay());
    }

    public function testExplainQuery(): void
    {
        $provider = $this->createMock(SchemaProviderInterface::class);
        $provider
            ->method('explainQuery')
            ->with('SELECT * FROM users', [], false)
            ->willReturn(['plan' => 'Seq Scan on users']);

        $tester = new CommandTester(new InspectDatabaseCommand($provider));
        $tester->execute(['action' => 'explain', 'target' => 'SELECT * FROM users']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('EXPLAIN', $tester->getDisplay());
    }

    public function testExplainAnalyze(): void
    {
        $provider = $this->createMock(SchemaProviderInterface::class);
        $provider->method('explainQuery')->with('SELECT 1', [], true)->willReturn(['plan' => 'Result']);

        $tester = new CommandTester(new InspectDatabaseCommand($provider));
        $tester->execute(['action' => 'explain', 'target' => 'SELECT 1', '--analyze' => true]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('EXPLAIN ANALYZE', $tester->getDisplay());
    }

    public function testUnknownAction(): void
    {
        $provider = $this->createMock(SchemaProviderInterface::class);

        $tester = new CommandTester(new InspectDatabaseCommand($provider));
        $tester->execute(['action' => 'invalid']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Unknown action', $tester->getDisplay());
    }
}
