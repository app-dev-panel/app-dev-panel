<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Debug\Controller;

use AppDevPanel\Api\Debug\Exception\NotFoundException;
use AppDevPanel\Api\Debug\Exception\PackageNotInstalledException;
use AppDevPanel\Api\Debug\HtmlViewProviderInterface;
use AppDevPanel\Api\Debug\ModuleFederationProviderInterface;
use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use AppDevPanel\Api\ServerSentEventsStream;
use AppDevPanel\Kernel\Storage\StorageInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\AssetPublisherInterface;
use Yiisoft\DataResponse\DataResponse;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Yii\View\ViewRenderer;

/**
 * Debug controller provides endpoints that expose information about requests processed that debugger collected.
 */
final class DebugController
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private CollectorRepositoryInterface $collectorRepository,
    ) {}

    /**
     * List of requests processed.
     */
    public function index(): ResponseInterface
    {
        return $this->responseFactory->createResponse($this->collectorRepository->getSummary());
    }

    /**
     * Summary about a processed request identified by ID specified.
     */
    public function summary(CurrentRoute $currentRoute): ResponseInterface
    {
        $data = $this->collectorRepository->getSummary($currentRoute->getArgument('id'));
        return $this->responseFactory->createResponse($data);
    }

    /**
     * Detail information about a processed request identified by ID.
     */
    public function view(
        CurrentRoute $currentRoute,
        ServerRequestInterface $serverRequest,
        ContainerInterface $container,
    ): ResponseInterface {
        $data = $this->collectorRepository->getDetail($currentRoute->getArgument('id'));

        $collectorClass = $serverRequest->getQueryParams()['collector'] ?? null;
        if ($collectorClass !== null) {
            $data = $data[$collectorClass] ?? throw new NotFoundException(sprintf(
                "Requested collector doesn't exist: %s.",
                $collectorClass,
            ));
        }
        if (is_subclass_of($collectorClass, HtmlViewProviderInterface::class)) {
            return $this->createHtmlPanelResponse($container, $collectorClass, $data);
        }
        if (is_subclass_of($collectorClass, ModuleFederationProviderInterface::class)) {
            return $this->createJsPanelResponse($container, $collectorClass, $data);
        }

        return $this->responseFactory->createResponse($data);
    }

    /**
     * Dump information about a processed request identified by ID.
     *
     * @throws NotFoundException
     * @return ResponseInterface response.
     */
    public function dump(CurrentRoute $currentRoute): ResponseInterface
    {
        $data = $this->collectorRepository->getDumpObject($currentRoute->getArgument('id'));

        if ($currentRoute->getArgument('collector') !== null) {
            if (isset($data[$currentRoute->getArgument('collector')])) {
                $data = $data[$currentRoute->getArgument('collector')];
            } else {
                throw new NotFoundException('Requested collector doesn\'t exists.');
            }
        }

        return $this->responseFactory->createResponse($data);
    }

    /**
     * Object information about a processed request identified by ID.
     *
     * @return ResponseInterface response.
     */
    public function object(CurrentRoute $currentRoute): ResponseInterface
    {
        $data = $this->collectorRepository->getObject(
            $currentRoute->getArgument('id'),
            $currentRoute->getArgument('objectId'),
        );

        if (null === $data) {
            throw new NotFoundException('Requested objectId doesn\'t exists.');
        }

        return $this->responseFactory->createResponse([
            'class' => $data[0],
            'value' => $data[1],
        ]);
    }

    public function eventStream(StorageInterface $storage, ResponseFactoryInterface $responseFactory): ResponseInterface
    {
        // TODO implement OS signal handling
        $compareFunction = static function () use ($storage) {
            $read = $storage->read(StorageInterface::TYPE_SUMMARY, null);
            return md5(json_encode($read, JSON_THROW_ON_ERROR));
        };
        $hash = $compareFunction();
        $maxRetries = 10;
        $retries = 0;

        return $responseFactory
            ->createResponse()
            ->withHeader('Content-Type', 'text/event-stream')
            ->withHeader('Cache-Control', 'no-cache')
            ->withHeader('Connection', 'keep-alive')
            ->withBody(new ServerSentEventsStream(static function (array &$buffer) use (
                $compareFunction,
                &$hash,
                &$retries,
                $maxRetries,
            ) {
                $newHash = $compareFunction();

                if ($hash !== $newHash) {
                    $response = [
                        'type' => 'debug-updated',
                        'payload' => [],
                    ];

                    $buffer[] = json_encode($response);
                    $hash = $newHash;
                }

                // break the loop if the client aborted the connection (closed the page)
                if (connection_aborted()) {
                    return false;
                }
                if ($retries++ > $maxRetries) {
                    return false;
                }

                sleep(1);

                return true;
            }));
    }

    private function createJsPanelResponse(
        ContainerInterface $container,
        string $collectorClass,
        mixed $data,
    ): DataResponse {
        $asset = $collectorClass::getAsset();
        $module = $asset->getModule();
        $scope = $asset->getScope();
        /**
         * @psalm-suppress UndefinedClass
         */
        if (
            !class_exists(AssetManager::class)
            || !class_exists(AssetPublisherInterface::class)
            || !$container->has(AssetManager::class)
            || !$container->has(AssetPublisherInterface::class)
        ) {
            throw new PackageNotInstalledException('yiisoft/assets', sprintf(
                '"%s" or "%s" is not defined in the dependency container.',
                AssetManager::class,
                AssetPublisherInterface::class,
            ));
        }
        /**
         * @psalm-suppress UndefinedClass
         */
        $assetManager = $container->get(AssetManager::class);
        $assetManager->register($asset::class);
        /**
         * @psalm-suppress UndefinedClass
         */
        $assetPublisher = $container->get(AssetPublisherInterface::class);
        $assetPublisher->publish($asset);

        $js = $assetManager->getJsFiles();

        if (empty($js)) {
            return $this->responseFactory->createResponse([
                '__isPanelRemote__' => true,
                'url' => null,
                'module' => $module,
                'scope' => $scope,
                'data' => $data,
            ]);
        }

        $urls = end($js);

        return $this->responseFactory->createResponse([
            '__isPanelRemote__' => true,
            'url' => is_array($urls) && isset($urls[0]) ? $urls[0] : null,
            'module' => $module,
            'scope' => $scope,
            'data' => $data,
        ]);
    }

    private function createHtmlPanelResponse(
        ContainerInterface $container,
        string $collectorClass,
        mixed $data,
    ): DataResponse {
        if (!class_exists(ViewRenderer::class) || !$container->has(ViewRenderer::class)) {
            /**
             * @psalm-suppress UndefinedClass
             */
            throw new PackageNotInstalledException('yiisoft/yii-view', sprintf(
                '"%s" is not defined in the dependency container.',
                ViewRenderer::class,
            ));
        }
        $viewRenderer = $container->get(ViewRenderer::class);
        $viewDirectory = dirname($collectorClass::getView());
        $viewPath = basename($collectorClass::getView());

        return $viewRenderer->withViewPath($viewDirectory)->renderPartial($viewPath, [
            'data' => $data,
            'collectorClass' => $collectorClass,
        ]);
    }
}
