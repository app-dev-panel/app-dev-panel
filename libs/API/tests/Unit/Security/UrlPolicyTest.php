<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Security;

use AppDevPanel\Api\Security\NetworkAddressClassifier;
use AppDevPanel\Api\Security\UrlPolicy;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UrlPolicyTest extends TestCase
{
    /**
     * Deterministic resolver — the policy must never hit real DNS in tests.
     *
     * @return callable(string): list<string>
     */
    private static function resolver(): callable
    {
        return static fn(string $host): array => match ($host) {
            'python-app' => ['203.0.113.10'],
            'dual-stack.example' => ['203.0.113.11', '2001:db8::1'],
            'internal.corp' => ['10.0.0.5'],
            'rebinding.example' => ['203.0.113.12', '127.0.0.1'],
            'metadata.example' => ['169.254.169.254'],
            'mixed-metadata.example' => ['203.0.113.13', '169.254.169.254'],
            'mapped-loopback.example' => ['::ffff:127.0.0.1'],
            default => [],
        };
    }

    private function policy(bool $publicOnly = false): UrlPolicy
    {
        return new UrlPolicy($publicOnly, self::resolver());
    }

    /**
     * Accepted in both modes.
     *
     * @return iterable<string, array{string}>
     */
    public static function publicUrls(): iterable
    {
        yield 'public hostname' => ['http://python-app:9090'];
        yield 'https with path' => ['https://python-app/inspect/api'];
        yield 'dual stack public' => ['http://dual-stack.example:8080'];
        yield 'public ipv4 literal' => ['http://203.0.113.5:9090'];
        yield 'public ipv6 literal' => ['http://[2001:db8::5]:9090'];
        yield 'uppercase scheme' => ['HTTP://python-app'];
    }

    /**
     * Accepted by default, refused in public-hosts-only mode.
     *
     * @return iterable<string, array{string, string}>
     */
    public static function privateUrls(): iterable
    {
        yield 'localhost' => ['http://localhost:9090', 'loopback'];
        yield 'sub localhost' => ['http://app.localhost', 'loopback'];
        yield 'loopback v4' => ['http://127.0.0.1:9090', 'private or loopback'];
        yield 'loopback v4 other' => ['http://127.5.5.5', 'private or loopback'];
        yield 'loopback v6' => ['http://[::1]:9090', 'private or loopback'];
        yield 'rfc1918 10/8' => ['http://10.1.2.3', 'private or loopback'];
        yield 'rfc1918 172.16/12' => ['http://172.20.0.1', 'private or loopback'];
        yield 'rfc1918 192.168/16' => ['http://192.168.1.1', 'private or loopback'];
        yield 'cgnat 100.64/10' => ['http://100.64.0.1', 'private or loopback'];
        yield 'ula fd00::/8' => ['http://[fd12::1]', 'private or loopback'];
        yield 'site local fec0::/10' => ['http://[fec0::1]', 'private or loopback'];
        yield 'resolves to private' => ['http://internal.corp', '10.0.0.5'];
        yield 'rebinding mixed answers' => ['http://rebinding.example', '127.0.0.1'];
        yield 'resolves to mapped loopback' => ['http://mapped-loopback.example', '::ffff:127.0.0.1'];
    }

    /**
     * Refused in both modes.
     *
     * @return iterable<string, array{string, string}>
     */
    public static function forbiddenUrls(): iterable
    {
        yield 'ftp scheme' => ['ftp://python-app/x', 'scheme'];
        yield 'file scheme' => ['file:///etc/passwd', 'malformed'];
        yield 'javascript scheme' => ['javascript://python-app/%0aalert(1)', 'scheme'];
        yield 'gopher scheme' => ['gopher://python-app', 'scheme'];
        yield 'no scheme' => ['python-app:9090', 'malformed'];
        yield 'garbage' => ['http:///', 'malformed'];
        yield 'userinfo' => ['http://user:pass@python-app/', 'credentials'];
        yield 'user only' => ['http://user@python-app/', 'credentials'];
        yield 'userinfo on localhost' => ['http://root:secret@localhost', 'credentials'];
        yield 'metadata' => ['http://169.254.169.254/latest/meta-data', 'link-local'];
        yield 'link local v4 other' => ['http://169.254.1.1', 'link-local'];
        yield 'link local v6' => ['http://[fe80::1]', 'link-local'];
        yield 'mapped metadata' => ['http://[::ffff:169.254.169.254]', 'link-local'];
        yield 'unspecified v4' => ['http://0.0.0.0', 'link-local'];
        yield 'this network' => ['http://0.1.2.3', 'link-local'];
        yield 'unspecified v6' => ['http://[::]', 'link-local'];
        yield 'multicast v4' => ['http://224.0.0.1', 'link-local'];
        yield 'multicast v6' => ['http://[ff02::1]', 'link-local'];
        yield 'reserved 240/4' => ['http://240.0.0.1', 'link-local'];
        yield 'broadcast' => ['http://255.255.255.255', 'link-local'];
        yield 'resolves to metadata' => ['http://metadata.example', '169.254.169.254'];
        yield 'mixed public and metadata answers' => ['http://mixed-metadata.example', '169.254.169.254'];
        yield 'unresolvable' => ['http://does-not-resolve.invalid', 'could not be resolved'];
    }

    #[DataProvider('publicUrls')]
    public function testPublicUrlsAreAllowedInBothModes(string $url): void
    {
        $this->assertTrue($this->policy()->isAllowed($url));
        $this->assertTrue($this->policy(publicOnly: true)->isAllowed($url));
        $this->policy(publicOnly: true)->assertAllowed($url);
        $this->addToAssertionCount(1);
    }

    #[DataProvider('privateUrls')]
    public function testPrivateUrlsAreAllowedByDefault(string $url): void
    {
        $this->assertTrue($this->policy()->isAllowed($url));
        $this->policy()->assertAllowed($url);
        $this->addToAssertionCount(1);
    }

    #[DataProvider('privateUrls')]
    public function testPrivateUrlsAreRejectedInPublicHostsOnlyMode(string $url, string $reasonFragment): void
    {
        $policy = $this->policy(publicOnly: true);

        $this->assertFalse($policy->isAllowed($url));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($reasonFragment);
        $policy->assertAllowed($url);
    }

    #[DataProvider('forbiddenUrls')]
    public function testForbiddenUrlsAreRejectedByDefault(string $url, string $reasonFragment): void
    {
        $this->assertFalse($this->policy()->isAllowed($url));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($reasonFragment);
        $this->policy()->assertAllowed($url);
    }

    #[DataProvider('forbiddenUrls')]
    public function testForbiddenUrlsAreRejectedInPublicHostsOnlyMode(string $url, string $reasonFragment): void
    {
        $policy = $this->policy(publicOnly: true);

        $this->assertFalse($policy->isAllowed($url));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($reasonFragment);
        $policy->assertAllowed($url);
    }

    public function testDefaultPolicyAcceptsLocalhostAndRefusesMetadata(): void
    {
        $policy = new UrlPolicy(resolver: static fn(): array => []);

        $this->assertFalse($policy->restrictsToPublicHosts());
        $this->assertTrue($policy->isAllowed('http://localhost:9090'));
        $this->assertTrue($policy->isAllowed('http://127.0.0.1:9090'));
        $this->assertFalse($policy->isAllowed('http://169.254.169.254/latest/meta-data'));
    }

    public function testPublicHostsOnlyFlagIsReported(): void
    {
        $this->assertTrue(new UrlPolicy(true, static fn(): array => [])->restrictsToPublicHosts());
    }

    public function testLiteralIpsDoNotUseTheResolver(): void
    {
        $calls = 0;
        $resolver = static function () use (&$calls): array {
            $calls++;

            return ['203.0.113.1'];
        };

        foreach ([new UrlPolicy(false, $resolver), new UrlPolicy(true, $resolver)] as $policy) {
            $policy->assertAllowed('http://203.0.113.9');
            $policy->assertAllowed('http://[2001:db8::9]');
        }
        $this->assertSame(0, $calls);

        new UrlPolicy(false, $resolver)->assertAllowed('http://python-app');
        $this->assertSame(1, $calls);
    }

    public function testLocalhostDoesNotUseTheResolver(): void
    {
        $calls = 0;
        $policy = new UrlPolicy(false, static function () use (&$calls): array {
            $calls++;

            return [];
        });

        $policy->assertAllowed('http://localhost:9090');
        $policy->assertAllowed('http://app.localhost');
        $this->assertSame(0, $calls);
    }

    /**
     * @return iterable<string, array{string, bool, bool, bool}>
     */
    public static function classifiedAddresses(): iterable
    {
        // address, forbidden, private, public
        yield 'public v4' => ['8.8.8.8', false, false, true];
        yield 'public v6' => ['2001:db8::1', false, false, true];
        yield 'test-net' => ['203.0.113.7', false, false, true];
        yield 'loopback v4' => ['127.0.0.1', false, true, false];
        yield 'loopback v6' => ['::1', false, true, false];
        yield 'mapped loopback' => ['::ffff:127.0.0.1', false, true, false];
        yield 'mapped loopback hex' => ['::ffff:7f00:1', false, true, false];
        yield 'rfc1918' => ['10.0.0.1', false, true, false];
        yield 'rfc1918 172.31' => ['172.31.255.255', false, true, false];
        yield 'not rfc1918 172.32' => ['172.32.0.1', false, false, true];
        yield 'cgnat' => ['100.127.255.255', false, true, false];
        yield 'not cgnat' => ['100.128.0.1', false, false, true];
        yield 'ula' => ['fd00::1', false, true, false];
        yield 'metadata' => ['169.254.169.254', true, false, false];
        yield 'mapped metadata' => ['::ffff:169.254.169.254', true, false, false];
        yield 'link local v6' => ['fe80::1', true, false, false];
        yield 'link local v6 upper bound' => ['febf::1', true, false, false];
        yield 'not link local fec0' => ['fec0::1', false, true, false];
        yield 'unspecified v4' => ['0.0.0.0', true, false, false];
        yield 'unspecified v6' => ['::', true, false, false];
        yield 'multicast v4' => ['239.255.255.250', true, false, false];
        yield 'multicast v6' => ['ff02::1', true, false, false];
        yield 'reserved' => ['240.0.0.1', true, false, false];
        yield 'broadcast' => ['255.255.255.255', true, false, false];
        yield 'not an ip' => ['not-an-ip', true, false, false];
        yield 'empty' => ['', true, false, false];
    }

    #[DataProvider('classifiedAddresses')]
    public function testClassifier(string $address, bool $forbidden, bool $private, bool $public): void
    {
        $this->assertSame($forbidden, NetworkAddressClassifier::isForbiddenAddress($address), 'forbidden');
        $this->assertSame($private, NetworkAddressClassifier::isPrivateAddress($address), 'private');
        $this->assertSame($public, NetworkAddressClassifier::isPublicAddress($address), 'public');
    }

    public function testLoopbackHostnames(): void
    {
        $this->assertTrue(NetworkAddressClassifier::isLoopbackHostname('localhost'));
        $this->assertTrue(NetworkAddressClassifier::isLoopbackHostname('foo.localhost'));
        $this->assertTrue(NetworkAddressClassifier::isLoopbackHostname(''));
        $this->assertFalse(NetworkAddressClassifier::isLoopbackHostname('localhost.example.com'));
    }
}
