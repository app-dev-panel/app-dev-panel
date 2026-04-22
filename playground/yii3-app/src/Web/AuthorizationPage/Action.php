<?php

declare(strict_types=1);

namespace App\Web\AuthorizationPage;

use App\Auth\DemoIdentity;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Auth\IdentityRepositoryInterface;
use Yiisoft\Auth\IdentityWithTokenRepositoryInterface;
use Yiisoft\Rbac\ManagerInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final readonly class Action
{
    public function __construct(
        private ViewRenderer $viewRenderer,
        private CurrentUser $currentUser,
        private ManagerInterface $rbacManager,
        private IdentityRepositoryInterface $identityRepository,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $identity = $this->resolveIdentity($request);
        if ($identity !== null) {
            $this->currentUser->login($identity);
        }

        $userId = $this->currentUser->getId();
        $roles = $userId === null ? [] : $this->rbacManager->getRolesByUserId($userId);
        $permissions = [];
        foreach (['view-dashboard', 'edit-post', 'delete-post'] as $permission) {
            $permissions[$permission] = $userId !== null && $this->rbacManager->userHasPermission($userId, $permission);
        }

        return $this->viewRenderer->render(__DIR__ . '/template', [
            'identity' => $identity instanceof DemoIdentity ? $identity : null,
            'isGuest' => $this->currentUser->isGuest(),
            'userId' => $userId,
            'roles' => array_keys($roles),
            'permissions' => $permissions,
        ]);
    }

    private function resolveIdentity(ServerRequestInterface $request): ?DemoIdentity
    {
        $params = $request->getQueryParams();
        $token = is_string($params['token'] ?? null) ? $params['token'] : null;
        if ($token === null) {
            return null;
        }
        if (!$this->identityRepository instanceof IdentityWithTokenRepositoryInterface) {
            return null;
        }
        $identity = $this->identityRepository->findIdentityByToken($token, 'api');

        return $identity instanceof DemoIdentity ? $identity : null;
    }
}
