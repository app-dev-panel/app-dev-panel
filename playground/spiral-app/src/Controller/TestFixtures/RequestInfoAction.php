<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use Psr\Http\Message\ServerRequestInterface;

final class RequestInfoAction
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(ServerRequestInterface $request): array
    {
        return [
            'fixture' => 'request:basic',
            'status' => 'ok',
            'method' => $request->getMethod(),
            'path' => $request->getUri()->getPath(),
            'host' => $request->getUri()->getHost(),
            'query' => $request->getQueryParams(),
            'headers_seen' => array_keys($request->getHeaders()),
        ];
    }
}
