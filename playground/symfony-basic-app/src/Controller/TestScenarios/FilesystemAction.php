<?php

declare(strict_types=1);

namespace App\Controller\TestScenarios;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test/scenarios/filesystem', name: 'test_filesystem', methods: ['GET'])]
final class FilesystemAction
{
    public function __invoke(): JsonResponse
    {
        $tmpFile = sys_get_temp_dir() . '/adp-test-scenario-' . uniqid() . '.txt';
        file_put_contents($tmpFile, 'ADP filesystem test scenario');
        file_get_contents($tmpFile);
        unlink($tmpFile);

        return new JsonResponse(['scenario' => 'filesystem:basic', 'status' => 'ok']);
    }
}
