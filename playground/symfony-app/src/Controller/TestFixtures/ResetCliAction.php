<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test/fixtures/reset-cli', name: 'test_reset_cli', methods: ['POST', 'GET'])]
final readonly class ResetCliAction
{
    public function __construct(
        private KernelInterface $kernel,
    ) {}

    public function __invoke(): JsonResponse
    {
        $consolePath = $this->kernel->getProjectDir() . '/bin/console';

        $process = new Process(['php', $consolePath, 'debug:reset']);
        $process->setTimeout(10);
        $process->run();

        return new JsonResponse([
            'fixture' => 'reset-cli',
            'status' => $process->isSuccessful() ? 'ok' : 'error',
            'exitCode' => $process->getExitCode(),
            'output' => trim($process->getOutput()),
            'error' => trim($process->getErrorOutput()),
        ]);
    }
}
