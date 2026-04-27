<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;

final class HttpClientAction
{
    public function __construct(
        private readonly ClientInterface $httpClient,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        $factory = new Psr17Factory();
        $codes = [];
        foreach (['/one', '/two', '/three', '/four', '/five', '/six'] as $path) {
            $request = $factory->createRequest('GET', 'http://loopback.test' . $path);
            $codes[] = $this->httpClient->sendRequest($request)->getStatusCode();
        }

        return [
            'fixture' => 'http-client:basic',
            'status' => 'ok',
            'codes' => $codes,
        ];
    }
}
