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

namespace Hgraca\ContextMapper\Test\StubProjectSrc\Core\Component\X\Application\Service;

use Hgraca\ContextMapper\Test\StubProjectSrc\Core\Port\EventDispatcher\EventDispatcherInterface;
use Hgraca\ContextMapper\Test\StubProjectSrc\Core\Port\EventDispatcher\EventInterface;
use Hgraca\ContextMapper\Test\StubProjectSrc\Core\SharedKernel\Event\AaaEvent;
use Hgraca\ContextMapper\Test\StubProjectSrc\Core\SharedKernel\Event\BbbEvent;
use Hgraca\ContextMapper\Test\StubProjectSrc\Core\SharedKernel\Event\CccEvent;
use Hgraca\ContextMapper\Test\StubProjectSrc\Core\SharedKernel\Event\DddEvent;
use Hgraca\ContextMapper\Test\StubProjectSrc\Core\SharedKernel\Event\EeeEvent;
use Hgraca\ContextMapper\Test\StubProjectSrc\Infrastructure\UnknownEvent;

final class XxxAaaService
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function methodA(): void
    {
        $this->methodC();
    }

    public function methodB(): void
    {
        $this->methodC(new BbbEvent());
    }

    public function methodC(EventInterface $event = null): void
    {
        $event = $event ?: CccEvent::namedConstructor();

        $this->eventDispatcher->dispatch($event);
    }

    public function methodD(): void
    {
        $event = new DddEvent();

        $this->eventDispatcher->dispatch($event);
    }

    public function methodE(): void
    {
        $this->eventDispatcher->dispatch(new EeeEvent());
    }

    public function methodF(AaaEvent $event): void
    {
        $this->eventDispatcher->dispatch($event);
    }

    public function methodG(): void
    {
        $this->eventDispatcher->dispatch(CccEvent::namedConstructor());
    }

    public function methodH(): void
    {
        $this->eventDispatcher->dispatch(UnknownEvent::namedConstructor());
    }
}
