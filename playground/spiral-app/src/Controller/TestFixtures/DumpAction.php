<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use Symfony\Component\VarDumper\VarDumper;

final class DumpAction
{
    /**
     * @return array<string, string>
     */
    public function __invoke(): array
    {
        // NOTE: the playground depends on yiisoft/var-dumper (transitively via Kernel) which
        // registers its own global `dump()` first via composer autoload files, shadowing Symfony's.
        // We call Symfony's VarDumper directly here so the ADP VarDumperCollector sees the data.
        VarDumper::dump('simple string dump');
        VarDumper::dump(['array' => 'dump', 'with' => ['nested' => 'values']]);
        VarDumper::dump(new \stdClass());

        return ['fixture' => 'var-dumper:basic', 'status' => 'ok'];
    }
}
