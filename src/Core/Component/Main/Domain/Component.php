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

use Hgraca\ContextMapper\Core\Port\Parser\AstMapInterface;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\AstMap;

final class Component
{
    /**
     * @var string
     */
    private $name;

    /** @var UseCaseNode[]|DomainNodeCollection */
    private $useCaseList;

    /** @var ListenerNode[]|DomainNodeCollection */
    private $listenerList;

    /** @var ListenerNode[]|DomainNodeCollection */
    private $subscriberList;

    /**
     * @var EventDispatchingNode[]|DomainNodeCollection
     */
    private $eventDispatchingList;

    /**
     * @var AstMap
     */
    private $astMap;

    public function __construct(
        string $name,
        AstMapInterface $astMap,
        DomainNodeCollection $useCaseList = null,
        DomainNodeCollection $listenerList = null,
        DomainNodeCollection $subscriberList = null,
        DomainNodeCollection $eventDispatchingList = null
    ) {
        $this->name = $name;
        $this->astMap = $astMap;
        $this->useCaseList = $useCaseList ?? new DomainNodeCollection();
        $this->listenerList = $listenerList ?? new DomainNodeCollection();
        $this->subscriberList = $subscriberList ?? new DomainNodeCollection();
        $this->eventDispatchingList = $eventDispatchingList ?? new DomainNodeCollection();

        foreach ($this->useCaseList as $useCase) {
            $useCase->setComponent($this);
        }
        foreach ($this->listenerList as $listener) {
            $listener->setComponent($this);
        }
        foreach ($this->subscriberList as $subscriber) {
            $subscriber->setComponent($this);
        }
        foreach ($this->eventDispatchingList as $eventDispatching) {
            $eventDispatching->setComponent($this);
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAstMap(): AstMap
    {
        return $this->astMap;
    }

    /**
     * @return UseCaseNode[]|DomainNodeCollection
     */
    public function getUseCaseList(): DomainNodeCollection
    {
        return $this->useCaseList;
    }

    /**
     * @return ListenerNode[]|DomainNodeCollection
     */
    public function getListenerList(): DomainNodeCollection
    {
        return $this->listenerList;
    }

    /**
     * @return ListenerNode[]|DomainNodeCollection
     */
    public function getSubscriberList(): DomainNodeCollection
    {
        return $this->subscriberList;
    }

    /**
     * @return EventDispatchingNode[]|DomainNodeCollection
     */
    public function getEventDispatchingList(): DomainNodeCollection
    {
        return $this->eventDispatchingList;
    }
}
