<?php

declare(strict_types=1);

return [
    Hgraca\ContextMapper\Infrastructure\Framework\Symfony\CompilerPass\CommandCollectorCompilerPass::class => ['all' => true],
    Hgraca\ContextMapper\Test\Framework\CompilerPass\CreateTestContainer\CreateTestContainerCompilerPass::class => ['test' => true],
];
