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

use Hgraca\ContextMapper\Core\Port\Parser\Node\MethodCallInterface;

final class EventDispatcherNode extends MethodCallerNode
{
    /** @var string */
    private $eventCanonicalName;

    /** @var string */
    private $eventFqcn;

    /**
     * @return static
     */
    public static function constructFromNode(MethodCallInterface $methodCall): self
    {
        $self = new self();

        $self->dispatcherClassCanonicalName = $methodCall->getEnclosingClassCanonicalName();
        $self->dispatcherClassFqcn = $methodCall->getEnclosingClassFullyQualifiedName();
        $self->dispatcherMethod = $methodCall->getEnclosingMethodCanonicalName();
        $self->eventCanonicalName = $methodCall->getArgumentCanonicalType();
        $self->eventFqcn = $methodCall->getArgumentFullyQualifiedType();
        $self->methodCallLine = $methodCall->getLine();

        return $self;
    }

    public function getEventCanonicalName(): string
    {
        return $this->eventCanonicalName;
    }

    public function getEventFullyQualifiedName(): string
    {
        return $this->eventFqcn;
    }
}
