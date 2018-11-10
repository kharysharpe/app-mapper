<?php

declare(strict_types=1);

/*
 * This file is part of the Context Mapper application,
 * following the Explicit Architecture principles.
 *
 * @link https://herbertograca.com/2017/11/16/explicit-architecture-01-ddd-hexagonal-onion-clean-cqrs-how-i-put-it-all-together
 * @link https://herbertograca.com/2018/07/07/more-than-concentric-layers/
 *
 * (c) Herberto Graça
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Acme\App\Infrastructure\Framework\Symfony;

use Symfony\Component\Console\Application as SymfonyConsoleApplication;

final class CliApplication extends SymfonyConsoleApplication
{
    /** @var CliKernel */
    private $kernel;

    public function getKernel(): CliKernel
    {
        return $this->kernel;
    }

    public function setKernel(CliKernel $kernel): void
    {
        $this->kernel = $kernel;
    }
}