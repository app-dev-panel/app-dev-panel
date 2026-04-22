<?php

declare(strict_types=1);

use App\Auth\DemoIdentityRepository;
use App\Auth\RbacSeeder;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Yiisoft\Access\AccessCheckerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Auth\AuthenticationMethodInterface;
use Yiisoft\Auth\IdentityRepositoryInterface;
use Yiisoft\Auth\IdentityWithTokenRepositoryInterface;
use Yiisoft\Auth\Method\Composite;
use Yiisoft\Auth\Method\HttpBasic;
use Yiisoft\Auth\Method\HttpBearer;
use Yiisoft\Auth\Method\QueryParameter;
use Yiisoft\Rbac\AssignmentsStorageInterface;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\Manager;
use Yiisoft\Rbac\ManagerInterface;
use Yiisoft\Rbac\Php\AssignmentsStorage as PhpAssignmentsStorage;
use Yiisoft\Rbac\Php\ItemsStorage as PhpItemsStorage;
use Yiisoft\Rbac\RuleFactoryInterface;
use Yiisoft\Rbac\SimpleRuleFactory;
use Yiisoft\User\CurrentUser;

return [
    IdentityRepositoryInterface::class => DemoIdentityRepository::class,
    IdentityWithTokenRepositoryInterface::class => DemoIdentityRepository::class,

    ItemsStorageInterface::class => static function (Aliases $aliases): ItemsStorageInterface {
        $directory = $aliases->get('@runtime/rbac');
        if (!is_dir($directory)) {
            mkdir($directory, 0o777, true);
        }
        return new PhpItemsStorage($directory . '/items.php');
    },
    AssignmentsStorageInterface::class => static function (Aliases $aliases): AssignmentsStorageInterface {
        $directory = $aliases->get('@runtime/rbac');
        if (!is_dir($directory)) {
            mkdir($directory, 0o777, true);
        }
        return new PhpAssignmentsStorage($directory . '/assignments.php');
    },
    RuleFactoryInterface::class => SimpleRuleFactory::class,
    ManagerInterface::class => Manager::class,
    AccessCheckerInterface::class => static fn(ManagerInterface $manager): AccessCheckerInterface => $manager,

    AuthenticationMethodInterface::class =>
        static fn(ContainerInterface $container): AuthenticationMethodInterface => new Composite([
            $container->get(HttpBearer::class),
            $container->get(HttpBasic::class),
            $container->get(QueryParameter::class),
        ]),

    CurrentUser::class => static fn(
        IdentityRepositoryInterface $identityRepository,
        EventDispatcherInterface $eventDispatcher,
        AccessCheckerInterface $accessChecker,
    ): CurrentUser => new CurrentUser($identityRepository, $eventDispatcher)->withAccessChecker($accessChecker),

    RbacSeeder::class => RbacSeeder::class,
];
