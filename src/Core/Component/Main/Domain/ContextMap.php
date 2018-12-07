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

use Hgraca\ContextMapper\Core\Component\Main\Domain\Node\EventDispatcherNode;
use Hgraca\ContextMapper\Core\Component\Main\Domain\Node\ListenerNode;

final class ContextMap
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var Component[]
     */
    private $componentList = [];

    private function __construct()
    {
    }

    public static function construct(string $name, Component ...$componentList): self
    {
        $self = new self();

        $self->name = $name;
        $self->addComponents(...$componentList);

        return $self;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Component[]
     */
    public function getComponentList(): array
    {
        return $this->componentList;
    }

    /**
     * @return ListenerNode[]|DomainNodeCollection
     */
    public function getListenersOf(EventDispatcherNode $eventDispatcher): DomainNodeCollection
    {
        $listenersList = [];
        foreach ($this->getComponentList() as $component) {
            foreach ($component->getListenerCollection() as $listener) {
                if ($listener->listensTo($eventDispatcher)) {
                    $listenersList[] = $listener;
                }
            }
            foreach ($component->getSubscriberCollection() as $subscriber) {
                if ($subscriber->listensTo($eventDispatcher)) {
                    $listenersList[] = $subscriber;
                }
            }
        }

        return new DomainNodeCollection(...$listenersList);
    }

    private function addComponents(Component ...$componentList): void
    {
        $this->componentList = array_merge($this->componentList, $componentList);
    }
}
