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

namespace Hgraca\ContextMapper\Core\Component\Main\Application\Service;

use Hgraca\ContextMapper\Core\Component\Main\Application\Query\EventDispatchingQuery;
use Hgraca\ContextMapper\Core\Component\Main\Application\Query\ListenerQuery;
use Hgraca\ContextMapper\Core\Component\Main\Application\Query\MethodCallerQuery;
use Hgraca\ContextMapper\Core\Component\Main\Application\Query\SubscriberQuery;
use Hgraca\ContextMapper\Core\Component\Main\Application\Query\UseCaseQuery;
use Hgraca\ContextMapper\Core\Component\Main\Domain\Component;
use Hgraca\ContextMapper\Core\Component\Main\Domain\ContextMap;
use Hgraca\ContextMapper\Core\Component\Main\Domain\MethodCallerNode;
use Hgraca\ContextMapper\Core\Port\Configuration\Configuration;
use Hgraca\ContextMapper\Core\Port\Parser\AstMapFactoryInterface;
use Hgraca\ContextMapper\Core\Port\Printer\PrinterInterface;

final class ContextMapService
{
    /**
     * @var PrinterInterface
     */
    private $printer;

    /**
     * @var AstMapFactoryInterface
     */
    private $astMapFactory;

    /**
     * @var UseCaseQuery
     */
    private $useCaseQuery;

    /**
     * @var ListenerQuery
     */
    private $listenerQuery;

    /**
     * @var SubscriberQuery
     */
    private $subscriberQuery;

    /**
     * @var EventDispatchingQuery
     */
    private $eventDispatchingQuery;

    public function __construct(
        PrinterInterface $printer,
        AstMapFactoryInterface $astMapFactory,
        UseCaseQuery $useCaseQuery,
        ListenerQuery $listenerQuery,
        SubscriberQuery $subscriberQuery,
        EventDispatchingQuery $eventDispatchingQuery
    ) {
        $this->printer = $printer;
        $this->astMapFactory = $astMapFactory;
        $this->useCaseQuery = $useCaseQuery;
        $this->listenerQuery = $listenerQuery;
        $this->subscriberQuery = $subscriberQuery;
        $this->eventDispatchingQuery = $eventDispatchingQuery;
    }

    public function printContextMap(ContextMap $contextMap, Configuration $config): void
    {
        file_put_contents(
            $config->getOutputFileAbsPath(),
            $this->printer->printToImage($contextMap, $config)
        );
    }

    public function createFromConfig(Configuration $config): ContextMap
    {
        $componentList = [];
        foreach ($config->getComponents() as $componentDto) {
            $componentAstMap = $componentDto->isDir()
                ? $this->astMapFactory->constructFromFolder($componentDto->getPath())
                : $this->astMapFactory->unserializeFromFile($componentDto->getPath());

            $componentList[] = new Component(
                $componentDto->getName(),
                $componentAstMap, // FIXME all this below should be encapsulated
                $this->useCaseQuery->queryAst($componentAstMap, $config->getUseCaseCollector()),
                $this->listenerQuery->queryAst($componentAstMap, $config->getListenerCollector()),
                $this->subscriberQuery->queryAst($componentAstMap, $config->getSubscriberCollector()),
                $this->eventDispatchingQuery->queryAst($componentAstMap, $config->getEventDispatchingCollector())
            );
        }

        return ContextMap::construct($config->getTitle())->addComponents(...$componentList);
    }
}
