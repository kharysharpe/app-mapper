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

namespace Hgraca\ContextMapper\Core\Component\Main\Domain;

use Hgraca\ContextMapper\Core\Port\Parser\Node\MethodCallInterface;

final class DispatchedEventNode implements DomainNodeInterface
{
    /** @var string */
    private $dispatcherClass;

    /** @var string */
    private $dispatcherMethod;

    /** @var string */
    private $event;

    /** @var string */
    private $dispatcherClassFqcn;

    /** @var string */
    private $eventFqcn;

    private function __construct()
    {
    }

    public static function constructFromMethodCall(MethodCallInterface $methodCall): self
    {
        $node = new self();
        $node->dispatcherClass = $methodCall->getEnclosingClassCanonicalName();
        $node->dispatcherMethod = $methodCall->getEnclosingMethodCanonicalName();
        $node->event = $methodCall->getArgumentCanonicalType();
        $node->dispatcherClassFqcn = $methodCall->getEnclosingClassFullyQualifiedName();
        $node->eventFqcn = $methodCall->getArgumentFullyQualifiedType();

        return $node;
    }

    public function toArray(): array
    {
        return [
            'Dispatcher Class' => $this->dispatcherClass,
            'Dispatcher Method' => $this->dispatcherMethod,
            'Event' => $this->event,
            'Dispatcher FQCN' => $this->dispatcherClassFqcn,
            'Event FQCN' => $this->eventFqcn,
        ];
    }
}
