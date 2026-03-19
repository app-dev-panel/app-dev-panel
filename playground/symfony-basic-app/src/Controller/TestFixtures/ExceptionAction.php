<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test/fixtures/exception', name: 'test_exception', methods: ['GET'])]
final class ExceptionAction
{
    public function __invoke(): never
    {
        throw new \RuntimeException('ADP test fixture exception');
    }
}
