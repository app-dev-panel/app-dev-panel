<?php

declare(strict_types=1);

/**
 * Test-only stand-in for the phpredis `\RedisException` class.
 * Loaded by tests/bootstrap.php only when ext-redis is absent.
 */
class RedisException extends RuntimeException {}
