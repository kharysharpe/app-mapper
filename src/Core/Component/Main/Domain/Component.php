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
use Hgraca\ContextMapper\Core\Component\Main\Domain\Node\UseCaseNode;

final class Component
{
    /**
     * @var string
     */
    private $name;

    /** @var UseCaseNode[]|DomainNodeCollection */
    private $useCaseCollection;

    /** @var ListenerNode[]|DomainNodeCollection */
    private $listenerCollection;

    /** @var ListenerNode[]|DomainNodeCollection */
    private $subscriberCollection;

    /**
     * @var EventDispatcherNode[]|DomainNodeCollection
     */
    private $eventDispatcherCollection;

    /**
     * @var DomainAstMap
     */
    private $astMap;

    public function __construct(string $name, DomainAstMap $astMap)
    {
        $this->name = $name;
        $this->astMap = $astMap;

        $this->useCaseCollection = $astMap->findUseCases();
        foreach ($this->useCaseCollection as $useCase) {
            $useCase->setComponent($this);
        }

        $this->listenerCollection = $astMap->findListeners();
        foreach ($this->listenerCollection as $listener) {
            $listener->setComponent($this);
        }

        $this->subscriberCollection = $astMap->findSubscribers();
        foreach ($this->subscriberCollection as $subscriber) {
            $subscriber->setComponent($this);
        }

        $this->eventDispatcherCollection = $astMap->findEventDispatchers();
        foreach ($this->eventDispatcherCollection as $eventDispatcher) {
            $eventDispatcher->setComponent($this);
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAstMap(): DomainAstMap
    {
        return $this->astMap;
    }

    /**
     * @return UseCaseNode[]|DomainNodeCollection
     */
    public function getUseCaseCollection(): DomainNodeCollection
    {
        return $this->useCaseCollection;
    }

    /**
     * @return ListenerNode[]|DomainNodeCollection
     */
    public function getListenerCollection(): DomainNodeCollection
    {
        return $this->listenerCollection;
    }

    /**
     * @return ListenerNode[]|DomainNodeCollection
     */
    public function getSubscriberCollection(): DomainNodeCollection
    {
        return $this->subscriberCollection;
    }

    /**
     * @return EventDispatcherNode[]|DomainNodeCollection
     */
    public function getEventDispatcherCollection(): DomainNodeCollection
    {
        return $this->eventDispatcherCollection;
    }
}
