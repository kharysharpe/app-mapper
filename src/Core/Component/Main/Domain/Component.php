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

    public function __construct(
        string $name,
        DomainNodeCollection $useCaseList = null,
        DomainNodeCollection $listenerList = null,
        DomainNodeCollection $subscriberList = null
    ) {
        $this->name = $name;
        $this->useCaseList = $useCaseList ?? new DomainNodeCollection();
        $this->listenerList = $listenerList ?? new DomainNodeCollection();
        $this->subscriberList = $subscriberList ?? new DomainNodeCollection();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function addUseCase(UseCaseNode ...$useCaseList): void
    {
        array_merge($this->useCaseList, $useCaseList);
    }

    public function addListener(ListenerNode ...$listenerList): void
    {
        array_merge($this->listenerList, $listenerList);
    }

    public function addSubscriber(ListenerNode ...$subscriberList): void
    {
        array_merge($this->subscriberList, $subscriberList);
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
}
