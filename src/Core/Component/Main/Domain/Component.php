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

use Hgraca\ContextMapper\Core\Component\Main\Domain\Node\EventDispatchingNode;
use Hgraca\ContextMapper\Core\Component\Main\Domain\Node\ListenerNode;
use Hgraca\ContextMapper\Core\Component\Main\Domain\Node\MethodCallerNode;
use Hgraca\ContextMapper\Core\Component\Main\Domain\Node\UseCaseNode;
use Hgraca\ContextMapper\Core\Port\Configuration\Collector\ClassFqcnRegexCriteria;
use Hgraca\ContextMapper\Core\Port\Configuration\Collector\CodeUnitCollector;
use Hgraca\ContextMapper\Core\Port\Configuration\Collector\MethodNameRegexCriteria;
use Hgraca\PhpExtension\String\StringService;

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
     * @var EventDispatchingNode[]|DomainNodeCollection
     */
    private $eventDispatchingCollection;

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

        $this->eventDispatchingCollection = $astMap->findEventDispatching();
        foreach ($this->eventDispatchingCollection as $eventDispatching) {
            $eventDispatching->setComponent($this);
        }

        $this->resolveIntermediaryEventDispatchingRoots();
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
     * @return EventDispatchingNode[]|DomainNodeCollection
     */
    public function getEventDispatchingCollection(): DomainNodeCollection
    {
        return $this->eventDispatchingCollection;
    }

    public function hasUseCase(string $fqcn): bool
    {
        return $this->useCaseCollection->hasNodeWithFqcn($fqcn);
    }

    public function hasListener(string $fqcn): bool
    {
        return $this->listenerCollection->hasNodeWithFqcn($fqcn);
    }

    public function hasSubscriber(string $fqcn): bool
    {
        return $this->subscriberCollection->hasNodeWithFqcn($fqcn);
    }

    private function resolveIntermediaryEventDispatchingRoots(): void
    {
        $this->eventDispatchingCollection = $this->findEventDispatchingRoots($this->eventDispatchingCollection);
    }

    /**
     * @param MethodCallerNode[]|DomainNodeCollection $methodDispatcherNodeCollection
     *
     * @return MethodCallerNode[]|DomainNodeCollection
     */
    private function findEventDispatchingRoots(
        DomainNodeCollection $methodDispatcherNodeCollection
    ): DomainNodeCollection {
        $newEventDispatcherNodeCollection = new DomainNodeCollection();

        foreach ($methodDispatcherNodeCollection as $methodDispatcherNode) {
            $eventDispatchingNodeClassFqcn = $methodDispatcherNode->getDispatcherClassFqcn();

            if ($this->isEventDispatchingRoot($eventDispatchingNodeClassFqcn)) {
                $newEventDispatcherNodeCollection->addNodes($methodDispatcherNode);
                continue;
            }

            $eventDispatchingRootList = $this->findEventDispatchingRoots(
                $this->astMap->findMethodCallers(
                    CodeUnitCollector::constructFromCriteria(
                        new ClassFqcnRegexCriteria(
                            '/' . StringService::replace('\\', '\\\\', $eventDispatchingNodeClassFqcn) . '/'
                        ),
                        new MethodNameRegexCriteria('/' . $methodDispatcherNode->getDispatcherMethod() . '/')
                    )
                )
            );

            if ($methodDispatcherNode instanceof EventDispatchingNode) {
                $list = [];
                foreach ($eventDispatchingRootList as $eventDispatchingRoot) {
                    $list[] = EventDispatchingNode::constructFromMethodDispatcherNode(
                        $eventDispatchingRoot,
                        $methodDispatcherNode->getEventFullyQualifiedName()
                    );
                }
                $eventDispatchingRootList = $list;
            }

            $newEventDispatcherNodeCollection->addNodes(...$eventDispatchingRootList);
        }

        return $newEventDispatcherNodeCollection;
    }

    private function isEventDispatchingRoot(string $eventDispatchingNodeFqcn): bool
    {
        return $this->hasUseCase($eventDispatchingNodeFqcn)
            || $this->hasListener($eventDispatchingNodeFqcn)
            || $this->hasSubscriber($eventDispatchingNodeFqcn);
    }
}
