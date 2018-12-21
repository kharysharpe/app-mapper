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

namespace Hgraca\AppMapper\Core\Component\Main\Domain;

use Hgraca\AppMapper\Core\Component\Main\Domain\Node\EventDispatcherNode;
use Hgraca\AppMapper\Core\Component\Main\Domain\Node\ListenerNode;
use Hgraca\AppMapper\Core\Component\Main\Domain\Node\PartialUseCaseNode;
use Hgraca\AppMapper\Core\Component\Main\Domain\Node\UseCaseNode;

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
     * @var PartialUseCaseNode[]|DomainNodeCollection
     */
    private $partialUseCaseNodeCollection;

    /**
     * @var DomainAstMap
     */
    private $astMap;

    public function __construct(string $name, DomainAstMap $astMap)
    {
        $this->name = $name;
        $this->astMap = $astMap;

        $this->useCaseCollection = $astMap->findUseCases($this->name);
        foreach ($this->useCaseCollection as $useCase) {
            $useCase->setComponent($this);
        }

        $this->listenerCollection = $astMap->findListeners($this->name);
        foreach ($this->listenerCollection as $listener) {
            $listener->setComponent($this);
        }

        $this->subscriberCollection = $astMap->findSubscribers($this->name);
        foreach ($this->subscriberCollection as $subscriber) {
            $subscriber->setComponent($this);
        }

        $this->partialUseCaseNodeCollection = new DomainNodeCollection();
        $this->eventDispatcherCollection = $astMap->findEventDispatchers($this->name);
        foreach ($this->eventDispatcherCollection as $eventDispatcher) {
            $eventDispatcher->setComponent($this);

            if (
                !$this->hasUseCase($eventDispatcher->getDispatcherClassFqcn())
                && !$this->hasListener($eventDispatcher->getDispatcherClassFqcn())
                && !$this->hasSubscriber($eventDispatcher->getDispatcherClassFqcn())
            ) {
                $this->partialUseCaseNodeCollection->addNodes(
                    PartialUseCaseNode::constructFromEventDispatcher($eventDispatcher)
                );
            }
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
     * @return UseCaseNode[]|DomainNodeCollection
     */
    public function getPartialUseCaseCollection(): DomainNodeCollection
    {
        return $this->partialUseCaseNodeCollection;
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

    private function hasUseCase(string $fqcn): bool
    {
        return $this->useCaseCollection->hasNodeWithFqcn($fqcn);
    }

    private function hasListener(string $fqcn): bool
    {
        return $this->listenerCollection->hasNodeWithFqcn($fqcn);
    }

    private function hasSubscriber(string $fqcn): bool
    {
        return $this->subscriberCollection->hasNodeWithFqcn($fqcn);
    }
}
