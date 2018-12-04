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

namespace Hgraca\ContextMapper\Core\Component\Main\Domain\Node;

use Hgraca\ContextMapper\Core\Component\Main\Domain\Component;
use Hgraca\ContextMapper\Core\Port\Parser\Node\MethodCallInterface;

final class EventDispatchingNode implements DomainNodeInterface
{
    /** @var string */
    private $eventCanonicalName;

    /** @var string */
    private $eventFqcn;

    /** @var string */
    private $dispatcherClassCanonicalName;

    /** @var string */
    private $dispatcherClassFqcn;

    /** @var string */
    private $dispatcherMethod;

    /**
     * @var Component|null
     */
    private $component;

    public function __construct(MethodCallInterface $methodCall)
    {
        $this->eventCanonicalName = $methodCall->getArgumentCanonicalType();
        $this->eventFqcn = $methodCall->getArgumentFullyQualifiedType();
        $this->dispatcherClassCanonicalName = $methodCall->getEnclosingClassCanonicalName();
        $this->dispatcherClassFqcn = $methodCall->getEnclosingClassFullyQualifiedName();
        $this->dispatcherMethod = $methodCall->getEnclosingMethodCanonicalName();
    }

    public function getEventCanonicalName(): string
    {
        return $this->eventCanonicalName;
    }

    public function getEventFullyQualifiedName(): string
    {
        return $this->eventFqcn;
    }

    public function getFullyQualifiedName(): string
    {
        return $this->dispatcherClassFqcn . '::' . $this->dispatcherMethod;
    }

    public function getCanonicalName(): string
    {
        return $this->dispatcherClassCanonicalName . '::' . $this->dispatcherMethod;
    }

    public function getAction(): string
    {
        return $this->dispatcherMethod;
    }

    public function setComponent(Component $component): void
    {
        $this->component = $component;
    }

    public function getComponent(): ?Component
    {
        return $this->component;
    }
}
