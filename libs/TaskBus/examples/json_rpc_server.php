<?php

/**
 * JSON-RPC server example — starts a TCP server for remote task management.
 *
 * Run: php examples/json_rpc_server.php
 * Test: echo '{"jsonrpc":"2.0","method":"task.submit","params":{"type":"run_command","command":"echo hi"},"id":1}' | nc localhost 9800
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

use AppDevPanel\TaskBus\Scheduler\ScheduleRegistry;
use AppDevPanel\TaskBus\Storage\PdoFactory;
use AppDevPanel\TaskBus\TaskBusConfig;
use AppDevPanel\TaskBus\TaskBusFactory;
use AppDevPanel\TaskBus\Transport\JsonRpcHandler;
use AppDevPanel\TaskBus\Transport\JsonRpcServer;

$config = new TaskBusConfig(
    databasePath: __DIR__ . '/task-bus.sqlite',
    defaultTimeout: 300,
);

$bus = TaskBusFactory::create($config);

$pdo = PdoFactory::create($config->databasePath);
$scheduleRegistry = new ScheduleRegistry($pdo);

$handler = new JsonRpcHandler($bus, $scheduleRegistry);
$server = new JsonRpcServer($handler);

$address = $argv[1] ?? 'tcp://127.0.0.1:9800';
echo "Starting TaskBus JSON-RPC server on {$address}...\n";
echo "Press Ctrl+C to stop.\n\n";
echo "Example requests:\n";
echo '  echo \'{"jsonrpc":"2.0","method":"task.submit","params":{"type":"run_command","command":"echo hello"},"id":1}\' | nc localhost 9800' . "\n";
echo '  echo \'{"jsonrpc":"2.0","method":"task.list","params":{},"id":2}\' | nc localhost 9800' . "\n";
echo '  echo \'{"jsonrpc":"2.0","method":"schedule.create","params":{"name":"every-5min","cron":"*/5 * * * *","type":"run_command","params":{"command":"date"}},"id":3}\' | nc localhost 9800' . "\n\n";

$server->listen($address);
