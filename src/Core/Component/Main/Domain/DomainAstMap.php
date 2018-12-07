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
use Hgraca\ContextMapper\Core\Port\Configuration\Collector\CodeUnitCollector;
use Hgraca\ContextMapper\Core\Port\Parser\AstMapInterface;
use Hgraca\ContextMapper\Core\Port\Parser\Node\ClassInterface;

final class DomainAstMap
{
    /**
     * @var AstMapInterface
     */
    private $astMap;

    /**
     * @var CodeUnitCollector
     */
    private $useCaseCollector;

    /**
     * @var CodeUnitCollector
     */
    private $listenerCollector;

    /**
     * @var CodeUnitCollector
     */
    private $subscriberCollector;

    /**
     * @var CodeUnitCollector
     */
    private $eventDispatcherCollector;

    public function __construct(
        AstMapInterface $astMap,
        CodeUnitCollector $useCaseCollector,
        CodeUnitCollector $listenerCollector,
        CodeUnitCollector $subscriberCollector,
        CodeUnitCollector $eventDispatcherCollector
    ) {
        $this->astMap = $astMap;
        $this->useCaseCollector = $useCaseCollector;
        $this->listenerCollector = $listenerCollector;
        $this->subscriberCollector = $subscriberCollector;
        $this->eventDispatcherCollector = $eventDispatcherCollector;
    }

    public function findUseCases(): DomainNodeCollection
    {
        $nodeCollection = $this->astMap->findClassesWithFqcnMatchingRegex(
            ...$this->useCaseCollector->getCriteriaListAsString()
        );

        return $nodeCollection->decorateByDomainNode(UseCaseNode::class);
    }

    public function findListeners(): DomainNodeCollection
    {
        $nodeCollection = $this->astMap->findClassesWithFqcnMatchingRegex(
            ...$this->listenerCollector->getCriteriaListAsString()
        );

        $listenerList = [];
        /* @var ClassInterface $classAdapter */
        foreach ($nodeCollection as $classAdapter) {
            foreach ($classAdapter->getMethodList() as $methodAdapter) {
                if ($methodAdapter->isConstructor() || !$methodAdapter->isPublic()) {
                    continue;
                }
                $listenerList[] = ListenerNode::constructFromClassAndMethod($classAdapter, $methodAdapter);
            }
        }

        return new DomainNodeCollection(...$listenerList);
    }

    public function findSubscribers(): DomainNodeCollection
    {
        $nodeCollection = $this->astMap->findClassesWithFqcnMatchingRegex(
            ...$this->subscriberCollector->getCriteriaListAsString()
        );

        $subscriberList = [];
        /* @var ClassInterface $classAdapter */
        foreach ($nodeCollection as $classAdapter) {
            foreach ($classAdapter->getMethodList() as $methodAdapter) {
                if ($methodAdapter->isConstructor() || !$methodAdapter->isPublic()) {
                    continue;
                }
                $subscriberList[] = ListenerNode::constructFromClassAndMethod($classAdapter, $methodAdapter);
            }
        }

        return new DomainNodeCollection(...$subscriberList);
    }

    public function findEventDispatchers(): DomainNodeCollection
    {
        return $this->astMap->findClassesCallingMethod(...$this->eventDispatcherCollector->getCriteriaListAsString())
            ->decorateByDomainNode(EventDispatcherNode::class);
    }
}
