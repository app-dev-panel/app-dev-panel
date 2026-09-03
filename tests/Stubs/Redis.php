<?php

declare(strict_types=1);

/**
 * Test-only stand-in for the phpredis `\Redis` class.
 *
 * Loaded by tests/bootstrap.php only when ext-redis is absent, so that
 * `RedisController` tests (which mock every method they touch) do not depend
 * on the ambient PHP extension set. Signatures mirror phpredis 6.x for the
 * subset of methods `AppDevPanel\Api\Inspector\Controller\RedisController` calls.
 *
 * Every method throws: the stub must never be used as a real client.
 */
class Redis
{
    public const int REDIS_NOT_FOUND = 0;
    public const int REDIS_STRING = 1;
    public const int REDIS_SET = 2;
    public const int REDIS_LIST = 3;
    public const int REDIS_ZSET = 4;
    public const int REDIS_HASH = 5;
    public const int REDIS_STREAM = 6;

    public function ping(?string $message = null): Redis|string|bool
    {
        throw self::notAvailable(__FUNCTION__);
    }

    public function info(string ...$sections): Redis|array|false
    {
        throw self::notAvailable(__FUNCTION__);
    }

    public function dbSize(): Redis|int|false
    {
        throw self::notAvailable(__FUNCTION__);
    }

    public function scan(mixed &$iterator, mixed $pattern = null, int $count = 0, ?string $type = null): array|false
    {
        throw self::notAvailable(__FUNCTION__);
    }

    public function type(string $key): Redis|int|false
    {
        throw self::notAvailable(__FUNCTION__);
    }

    public function ttl(string $key): Redis|int|false
    {
        throw self::notAvailable(__FUNCTION__);
    }

    public function get(string $key): mixed
    {
        throw self::notAvailable(__FUNCTION__);
    }

    public function lRange(string $key, int $start, int $end): Redis|array|false
    {
        throw self::notAvailable(__FUNCTION__);
    }

    public function sMembers(string $key): Redis|array|false
    {
        throw self::notAvailable(__FUNCTION__);
    }

    public function zRange(string $key, mixed $start, mixed $end, array|bool|null $options = null): Redis|array|false
    {
        throw self::notAvailable(__FUNCTION__);
    }

    public function hGetAll(string $key): Redis|array|false
    {
        throw self::notAvailable(__FUNCTION__);
    }

    public function xRange(string $key, string $start, string $end, int $count = -1): Redis|array|bool
    {
        throw self::notAvailable(__FUNCTION__);
    }

    public function del(array|string $key, string ...$other_keys): Redis|int|false
    {
        throw self::notAvailable(__FUNCTION__);
    }

    public function flushDB(?bool $sync = null): Redis|bool
    {
        throw self::notAvailable(__FUNCTION__);
    }

    private static function notAvailable(string $method): RedisException
    {
        return new RedisException(sprintf('Redis::%s() is a test stub; ext-redis is not loaded.', $method));
    }
}
