<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Validator\Rule\Email;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\ValidatorInterface;

final readonly class ValidatorAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private ValidatorInterface $validator,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Validate using Yii's Validator — the ValidatorInterfaceProxy intercepts
        // these calls and feeds validation results to ValidatorCollector.
        $rules = [
            'email' => [new Required(), new Email()],
            'name' => [new Required(), new Length(min: 2)],
        ];

        // Valid data
        $validResult = $this->validator->validate(['email' => 'user@example.com', 'name' => 'John'], $rules);

        // Invalid data
        $invalidResult = $this->validator->validate(['email' => 'not-an-email', 'name' => ''], $rules);

        return $this->responseFactory->createResponse([
            'fixture' => 'validator:basic',
            'status' => 'ok',
            'validErrors' => $validResult->getErrorMessagesIndexedByPath(),
            'invalidErrors' => $invalidResult->getErrorMessagesIndexedByPath(),
        ]);
    }
}
