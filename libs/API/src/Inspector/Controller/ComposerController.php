<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Inspector\Controller;

use Exception;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Yii\Debug\Api\Inspector\Command\BashCommand;
use Yiisoft\Yii\Debug\Api\Inspector\CommandResponse;

final class ComposerController
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
    ) {
    }

    public function index(Aliases $aliases): ResponseInterface
    {
        $composerJsonPath = $aliases->get('@root/composer.json');
        $composerLockPath = $aliases->get('@root/composer.lock');
        if (!file_exists($composerJsonPath)) {
            throw new Exception(
                sprintf(
                    'Could not find composer.json by the path "%s".',
                    $composerJsonPath,
                )
            );
        }
        $result = [
            'json' => json_decode(file_get_contents($composerJsonPath), true, 512, JSON_THROW_ON_ERROR),
            'lock' => file_exists($composerLockPath)
                ? json_decode(file_get_contents($composerLockPath), true, 512, JSON_THROW_ON_ERROR)
                : null,
        ];

        return $this->responseFactory->createResponse($result);
    }

    public function inspect(ServerRequestInterface $request, Aliases $aliases): ResponseInterface
    {
        $package = $request->getQueryParams()['package'] ?? null;
        if ($package === null) {
            throw new InvalidArgumentException(
                'Query parameter "package" should not be empty.'
            );
        }
        $command = new BashCommand($aliases, ['composer', 'show', $package, '--all', '--format=json']);
        $result = $command->run();

        return $this->responseFactory->createResponse([
            'status' => $result->getStatus(),
            'result' => $result->getStatus() === CommandResponse::STATUS_OK
                ? json_decode($result->getResult(), true, 512, JSON_THROW_ON_ERROR)
                : null,
            'errors' => $result->getErrors(),
        ]);
    }

    public function require(ServerRequestInterface $request, Aliases $aliases): ResponseInterface
    {
        // Request factory may be unable to parse JSON so don't rely on getParsedBody().
        $parsedBody = \json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $package = $parsedBody['package'] ?? null;
        $version = $parsedBody['version'] ?? null;
        $isDev = $parsedBody['isDev'] ?? false;
        if ($package === null) {
            throw new InvalidArgumentException(
                'Query parameter "package" should not be empty.'
            );
        }
        $packageWithVersion = sprintf('%s:%s', $package, $version ?? '*');
        $command = new BashCommand($aliases, [
            'composer',
            'require',
            $packageWithVersion,
            '-n',
            ...$isDev ? ['--dev'] : [],
        ]);
        $result = $command->run();

        return $this->responseFactory->createResponse([
            'status' => $result->getStatus(),
            'result' => !is_string($result->getResult())
                ? null
                : (
                    $result->getStatus() === CommandResponse::STATUS_OK
                        ? json_decode($result->getResult(), true, 512, JSON_THROW_ON_ERROR)
                        : $result->getResult()
                ),
            'errors' => $result->getErrors(),
        ]);
    }
}
