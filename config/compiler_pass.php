<?php

declare(strict_types=1);

return [
    Hgraca\AppMapper\Infrastructure\Framework\Symfony\CompilerPass\CommandCollectorCompilerPass::class => ['all' => true],
    Hgraca\AppMapper\Test\Framework\CompilerPass\CreateTestContainer\CreateTestContainerCompilerPass::class => ['test' => true],
];
