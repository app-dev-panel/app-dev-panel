<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

use AppDevPanel\Kernel\Collector\ValidatorCollector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

final readonly class ValidatorAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private ValidatorCollector $validatorCollector,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Simulate a passing validation
        $this->validatorCollector->collect(
            value: ['email' => 'user@example.com', 'name' => 'John'],
            isValid: true,
            rules: ['email' => 'required|email', 'name' => 'required|string|min:2'],
        );

        // Simulate a failing validation
        $this->validatorCollector->collect(
            value: ['email' => 'not-an-email', 'name' => ''],
            isValid: false,
            errors: [
                'email' => ['The email must be a valid email address.'],
                'name' => ['The name field is required.'],
            ],
            rules: ['email' => 'required|email', 'name' => 'required|string|min:2'],
        );

        return $this->responseFactory->createResponse(['fixture' => 'validator:basic', 'status' => 'ok']);
    }
}
