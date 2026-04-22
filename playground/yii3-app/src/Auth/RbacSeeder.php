<?php

declare(strict_types=1);

namespace App\Auth;

use Yiisoft\Rbac\AssignmentsStorageInterface;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\ManagerInterface;
use Yiisoft\Rbac\Permission;
use Yiisoft\Rbac\Role;

/**
 * Seeds the RBAC storages with a three-role hierarchy the first time the
 * application boots. Subsequent requests are no-ops: the seeder only writes
 * if the items storage is empty.
 *
 * Hierarchy (each parent inherits every child recursively):
 *
 *   admin  → editor → reader → view-dashboard
 *                   ↳ edit-post
 *          ↳ delete-post
 */
final class RbacSeeder
{
    public function __construct(
        private readonly ItemsStorageInterface $itemsStorage,
        private readonly AssignmentsStorageInterface $assignmentsStorage,
        private readonly ManagerInterface $manager,
    ) {}

    public function seed(): void
    {
        if ($this->itemsStorage->getAll() !== []) {
            return;
        }

        $admin = new Role('admin');
        $editor = new Role('editor');
        $reader = new Role('reader');

        $viewDashboard = new Permission('view-dashboard');
        $editPost = new Permission('edit-post');
        $deletePost = new Permission('delete-post');

        $this->manager->addRole($admin);
        $this->manager->addRole($editor);
        $this->manager->addRole($reader);
        $this->manager->addPermission($viewDashboard);
        $this->manager->addPermission($editPost);
        $this->manager->addPermission($deletePost);

        $this->manager->addChild('reader', 'view-dashboard');
        $this->manager->addChild('editor', 'reader');
        $this->manager->addChild('editor', 'edit-post');
        $this->manager->addChild('admin', 'editor');
        $this->manager->addChild('admin', 'delete-post');

        if ($this->assignmentsStorage->getByUserId('1') === []) {
            $this->manager->assign('admin', '1');
        }
        if ($this->assignmentsStorage->getByUserId('2') === []) {
            $this->manager->assign('editor', '2');
        }
        if ($this->assignmentsStorage->getByUserId('3') === []) {
            $this->manager->assign('reader', '3');
        }
    }
}
