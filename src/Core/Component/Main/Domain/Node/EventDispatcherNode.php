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

use Hgraca\ContextMapper\Core\Port\Logger\StaticLoggerFacade;
use Hgraca\ContextMapper\Core\Port\Parser\Node\AdapterNodeInterface;
use Hgraca\ContextMapper\Core\Port\Parser\Node\MethodArgumentInterface;
use Hgraca\ContextMapper\Core\Port\Parser\Node\MethodCallInterface;
use Hgraca\ContextMapper\Core\Port\Parser\Node\TypeNodeInterface;

final class EventDispatcherNode extends MethodCallerNode
{
    /**
     * @var MethodArgumentInterface|TypeNodeInterface[]
     */
    private $event;

    /**
     * @return static
     */
    public static function constructFromNode(MethodCallInterface $methodCall): self
    {
        $self = new self();

        $self->dispatcherClassCanonicalName = $methodCall->getEnclosingClassCanonicalName();
        $self->dispatcherClassFqcn = $methodCall->getEnclosingClassFullyQualifiedName();
        $self->dispatcherMethod = $methodCall->getEnclosingMethodCanonicalName();
        StaticLoggerFacade::notice(
            'TODO Currently we assume events are always the first argument. '
            . 'But this will need to be improved to accommodate projects that don\'t follow this coding standard',
            [__METHOD__]
        );
        $self->event = $methodCall->getMethodArgument(0);
        $self->methodCallLine = $methodCall->getLine();

        return $self;
    }

    /**
     * @return AdapterNodeInterface[]
     */
    public function getEventTypeList(): array
    {
        return $this->event->toArray();
    }

    public function dispatches(string $eventFqcn): bool
    {
        foreach ($this->event as $eventPossibleType) {
            if (
                $eventPossibleType->getFullyQualifiedType() === $eventFqcn
                || in_array($eventFqcn, $eventPossibleType->getAllFamilyFullyQualifiedNameList(), true)
            ) {
                return true;
            }
        }

        return false;
    }
}
