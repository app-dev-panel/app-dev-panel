<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Controller;

use AppDevPanel\Api\ApiSecurityConfig;
use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Api\Inspector\Command\BashCommand;
use AppDevPanel\Api\Inspector\CommandResponse;
use AppDevPanel\Api\PathResolverInterface;
use Closure;
use Exception;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ComposerController
{
    /** @var Closure(list<string>): CommandResponse */
    private readonly Closure $commandRunner;

    /**
     * @param null|callable(list<string>): CommandResponse $commandRunner Executes a `composer …` argv
     *        in the project root. Defaults to {@see BashCommand} (Symfony Process bounded by
     *        `CommandTimeout::DEFAULT` = 120 s); tests inject a fake so no process is spawned.
     */
    public function __construct(
        private readonly JsonResponseFactoryInterface $responseFactory,
        private readonly PathResolverInterface $pathResolver,
        private readonly ApiSecurityConfig $securityConfig = new ApiSecurityConfig(),
        ?callable $commandRunner = null,
    ) {
        $this->commandRunner = $commandRunner === null
            ? fn(array $command): CommandResponse => new BashCommand($this->pathResolver, $command)->run()
            : Closure::fromCallable($commandRunner);
    }

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $composerJsonPath = $this->pathResolver->getRootPath() . '/composer.json';
        $composerLockPath = $this->pathResolver->getRootPath() . '/composer.lock';
        if (!file_exists($composerJsonPath)) {
            throw new Exception(sprintf('Could not find composer.json by the path "%s".', $composerJsonPath));
        }
        $result = [
            'json' => json_decode(file_get_contents($composerJsonPath), true, 512, JSON_THROW_ON_ERROR),
            'lock' => file_exists($composerLockPath)
                ? json_decode(file_get_contents($composerLockPath), true, 512, JSON_THROW_ON_ERROR)
                : null,
        ];

        return $this->responseFactory->createJsonResponse($result);
    }

    public function inspect(ServerRequestInterface $request): ResponseInterface
    {
        $package = $request->getQueryParams()['package'] ?? null;
        if ($package === null) {
            throw new InvalidArgumentException('Query parameter "package" should not be empty.');
        }
        $result = ($this->commandRunner)(['composer', 'show', $package, '--all', '--format=json']);

        return $this->responseFactory->createJsonResponse([
            'status' => $result->getStatus(),
            'result' => $result->getStatus() === CommandResponse::STATUS_OK
                ? json_decode(self::extractJsonObject((string) $result->getResult()), true, 512, JSON_THROW_ON_ERROR)
                : null,
            'errors' => $result->getErrors(),
        ]);
    }

    /**
     * Extracts the first JSON object from a `composer` command output, stripping any
     * leading or trailing notices that composer prints to stderr (e.g. "Do not run
     * Composer as root" warnings) which `BashCommand` concatenates with stdout.
     */
    private static function extractJsonObject(string $output): string
    {
        $start = strpos($output, '{');
        $end = strrpos($output, '}');
        if ($start === false || $end === false || $end < $start) {
            return $output;
        }
        return substr($output, $start, $end - $start + 1);
    }

    public function require(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->securityConfig->allowDestructiveOperations) {
            return $this->responseFactory->createJsonResponse([
                'status' => CommandResponse::STATUS_ERROR,
                'errors' => [
                    'Composer require is disabled because allowDestructiveOperations is false. Composer package installation can execute arbitrary code via post-install scripts — enable it only on a trusted, authenticated deployment.',
                ],
            ], 403);
        }

        $parsedBody = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $package = $parsedBody['package'] ?? null;
        $version = $parsedBody['version'] ?? null;
        $isDev = $parsedBody['isDev'] ?? false;
        if ($package === null) {
            throw new InvalidArgumentException('Query parameter "package" should not be empty.');
        }
        $packageWithVersion = sprintf('%s:%s', $package, $version ?? '*');
        $result = ($this->commandRunner)([
            'composer',
            'require',
            $packageWithVersion,
            '-n',
            ...($isDev ? ['--dev'] : []),
        ]);

        return $this->responseFactory->createJsonResponse([
            'status' => $result->getStatus(),
            'result' => self::decodeResult($result),
            'errors' => $result->getErrors(),
        ]);
    }

    /**
     * `composer require` prints human-readable progress, not JSON: a successful run
     * returns the raw output unless it happens to be a JSON document.
     */
    private static function decodeResult(CommandResponse $result): mixed
    {
        $output = $result->getResult();
        if (!is_string($output)) {
            return null;
        }

        if ($result->getStatus() === CommandResponse::STATUS_OK && json_validate($output)) {
            return json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        }

        return $output;
    }
}
