<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\DependencyInjection;

use AppDevPanel\Adapter\Symfony\Collector\CacheCollector;
use AppDevPanel\Adapter\Symfony\Collector\DoctrineCollector;
use AppDevPanel\Adapter\Symfony\Collector\MailerCollector;
use AppDevPanel\Adapter\Symfony\Collector\MessengerCollector;
use AppDevPanel\Adapter\Symfony\Collector\SecurityCollector;
use AppDevPanel\Adapter\Symfony\Collector\SymfonyRequestCollector;
use AppDevPanel\Adapter\Symfony\Collector\TwigCollector;
use AppDevPanel\Adapter\Symfony\EventSubscriber\ConsoleSubscriber;
use AppDevPanel\Adapter\Symfony\EventSubscriber\HttpSubscriber;
use AppDevPanel\Kernel\Collector\Console\CommandCollector;
use AppDevPanel\Kernel\Collector\Console\ConsoleAppInfoCollector;
use AppDevPanel\Kernel\Collector\EventCollector;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\HttpClientCollector;
use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\Collector\ServiceCollector;
use AppDevPanel\Kernel\Collector\Stream\FilesystemStreamCollector;
use AppDevPanel\Kernel\Collector\Stream\HttpStreamCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Collector\VarDumperCollector;
use AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\FileStorage;
use AppDevPanel\Kernel\Storage\StorageInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

final class AppDevPanelExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if (!$config['enabled']) {
            return;
        }

        $container->setParameter('app_dev_panel.enabled', true);
        $container->setParameter('app_dev_panel.storage.path', $config['storage']['path']);
        $container->setParameter('app_dev_panel.storage.history_size', $config['storage']['history_size']);
        $container->setParameter('app_dev_panel.ignored_requests', $config['ignored_requests']);
        $container->setParameter('app_dev_panel.ignored_commands', $config['ignored_commands']);
        $container->setParameter('app_dev_panel.dumper.excluded_classes', $config['dumper']['excluded_classes']);

        $this->registerCoreServices($container, $config);
        $this->registerCollectors($container, $config);
        $this->registerEventSubscribers($container);
    }

    private function registerCoreServices(ContainerBuilder $container, array $config): void
    {
        $container->register(DebuggerIdGenerator::class, DebuggerIdGenerator::class)
            ->setPublic(false);

        $container->register(StorageInterface::class, FileStorage::class)
            ->setArguments([
                '%app_dev_panel.storage.path%',
                new Reference(DebuggerIdGenerator::class),
                '%app_dev_panel.dumper.excluded_classes%',
            ])
            ->setPublic(false);

        $container->register(TimelineCollector::class, TimelineCollector::class)
            ->setPublic(false)
            ->addTag('app_dev_panel.collector');
    }

    private function registerCollectors(ContainerBuilder $container, array $config): void
    {
        $collectors = $config['collectors'];

        if ($collectors['request']) {
            $container->register(SymfonyRequestCollector::class, SymfonyRequestCollector::class)
                ->setArguments([new Reference(TimelineCollector::class)])
                ->setPublic(false)
                ->addTag('app_dev_panel.collector')
                ->addTag('app_dev_panel.collector.web');

            $container->register(WebAppInfoCollector::class, WebAppInfoCollector::class)
                ->setPublic(false)
                ->addTag('app_dev_panel.collector')
                ->addTag('app_dev_panel.collector.web');
        }

        if ($collectors['exception']) {
            $container->register(ExceptionCollector::class, ExceptionCollector::class)
                ->setPublic(false)
                ->addTag('app_dev_panel.collector');
        }

        if ($collectors['log']) {
            $container->register(LogCollector::class, LogCollector::class)
                ->setPublic(false)
                ->addTag('app_dev_panel.collector');
        }

        if ($collectors['event']) {
            $container->register(EventCollector::class, EventCollector::class)
                ->setPublic(false)
                ->addTag('app_dev_panel.collector');
        }

        if ($collectors['service']) {
            $container->register(ServiceCollector::class, ServiceCollector::class)
                ->setPublic(false)
                ->addTag('app_dev_panel.collector');
        }

        if ($collectors['http_client']) {
            $container->register(HttpClientCollector::class, HttpClientCollector::class)
                ->setPublic(false)
                ->addTag('app_dev_panel.collector');
        }

        if ($collectors['var_dumper']) {
            $container->register(VarDumperCollector::class, VarDumperCollector::class)
                ->setPublic(false)
                ->addTag('app_dev_panel.collector');
        }

        if ($collectors['filesystem_stream']) {
            $container->register(FilesystemStreamCollector::class, FilesystemStreamCollector::class)
                ->setPublic(false)
                ->addTag('app_dev_panel.collector');
        }

        if ($collectors['http_stream']) {
            $container->register(HttpStreamCollector::class, HttpStreamCollector::class)
                ->setPublic(false)
                ->addTag('app_dev_panel.collector');
        }

        if ($collectors['command']) {
            $container->register(CommandCollector::class, CommandCollector::class)
                ->setPublic(false)
                ->addTag('app_dev_panel.collector')
                ->addTag('app_dev_panel.collector.console');

            $container->register(ConsoleAppInfoCollector::class, ConsoleAppInfoCollector::class)
                ->setPublic(false)
                ->addTag('app_dev_panel.collector')
                ->addTag('app_dev_panel.collector.console');
        }

        // Symfony-specific collectors
        if ($collectors['doctrine']) {
            $container->register(DoctrineCollector::class, DoctrineCollector::class)
                ->setArguments([new Reference(TimelineCollector::class)])
                ->setPublic(false)
                ->addTag('app_dev_panel.collector');
        }

        if ($collectors['twig']) {
            $container->register(TwigCollector::class, TwigCollector::class)
                ->setArguments([new Reference(TimelineCollector::class)])
                ->setPublic(false)
                ->addTag('app_dev_panel.collector');
        }

        if ($collectors['security']) {
            $container->register(SecurityCollector::class, SecurityCollector::class)
                ->setPublic(false)
                ->addTag('app_dev_panel.collector');
        }

        if ($collectors['cache']) {
            $container->register(CacheCollector::class, CacheCollector::class)
                ->setArguments([new Reference(TimelineCollector::class)])
                ->setPublic(false)
                ->addTag('app_dev_panel.collector');
        }

        if ($collectors['mailer']) {
            $container->register(MailerCollector::class, MailerCollector::class)
                ->setPublic(false)
                ->addTag('app_dev_panel.collector');
        }

        if ($collectors['messenger']) {
            $container->register(MessengerCollector::class, MessengerCollector::class)
                ->setArguments([new Reference(TimelineCollector::class)])
                ->setPublic(false)
                ->addTag('app_dev_panel.collector');
        }
    }

    private function registerEventSubscribers(ContainerBuilder $container): void
    {
        $container->register(HttpSubscriber::class, HttpSubscriber::class)
            ->setArguments([
                new Reference(Debugger::class),
                new Reference(SymfonyRequestCollector::class, ContainerBuilder::NULL_ON_INVALID_REFERENCE),
                new Reference(WebAppInfoCollector::class, ContainerBuilder::NULL_ON_INVALID_REFERENCE),
                new Reference(ExceptionCollector::class, ContainerBuilder::NULL_ON_INVALID_REFERENCE),
            ])
            ->addTag('kernel.event_subscriber')
            ->setPublic(false);

        $container->register(ConsoleSubscriber::class, ConsoleSubscriber::class)
            ->setArguments([
                new Reference(Debugger::class),
                new Reference(CommandCollector::class, ContainerBuilder::NULL_ON_INVALID_REFERENCE),
                new Reference(ConsoleAppInfoCollector::class, ContainerBuilder::NULL_ON_INVALID_REFERENCE),
                new Reference(ExceptionCollector::class, ContainerBuilder::NULL_ON_INVALID_REFERENCE),
            ])
            ->addTag('kernel.event_subscriber')
            ->setPublic(false);
    }

    public function getAlias(): string
    {
        return 'app_dev_panel';
    }
}
