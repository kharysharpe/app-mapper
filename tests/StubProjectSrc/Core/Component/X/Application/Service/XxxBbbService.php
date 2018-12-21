<?php

declare(strict_types=1);

/*
 * This file is part of the Application mapper application,
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

namespace Hgraca\AppMapper\Test\StubProjectSrc\Core\Component\X\Application\Service;

use Hgraca\AppMapper\Test\StubProjectSrc\Core\SharedKernel\Event\CccEvent;
use Hgraca\AppMapper\Test\StubProjectSrc\Core\SharedKernel\Event\DddEvent;

final class XxxBbbService
{
    /**
     * @var XxxAaaService
     */
    private $xxxAaaService;

    public function __construct(XxxAaaService $xxxAaaService)
    {
        $this->xxxAaaService = $xxxAaaService;
    }

    public function methodA(): void
    {
        $this->xxxAaaService->methodC(new CccEvent());
    }

    public function methodB(): void
    {
        $this->xxxAaaService->methodJ(new DddEvent());
    }

    public function methodC(): void
    {
        $event = $this->xxxAaaService->methodL();
    }

    public function methodD(): void
    {
        $event = $this->methodL();
    }

    public function methodL(): CccEvent
    {
        return CccEvent::namedConstructor();
    }
}
