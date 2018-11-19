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

use Hgraca\ContextMapper\Core\Component\Main\Application\Query\EventDispatcherQuery;
use Hgraca\ContextMapper\Core\Component\Main\Application\Query\ListenerQuery;
use Hgraca\ContextMapper\Core\Component\Main\Application\Query\SubscriberQuery;
use Hgraca\ContextMapper\Core\Component\Main\Application\Query\UseCaseQuery;
use Hgraca\ContextMapper\Core\Component\Main\Domain\Component;
use Hgraca\ContextMapper\Core\Component\Main\Domain\ContextMap;
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
     * @var EventDispatcherQuery
     */
    private $eventDispatcherQuery;

    public function __construct(
        PrinterInterface $printer,
        AstMapFactoryInterface $astMapFactory,
        UseCaseQuery $useCaseQuery,
        ListenerQuery $listenerQuery,
        SubscriberQuery $subscriberQuery,
        EventDispatcherQuery $eventDispatcherQuery
    ) {
        $this->printer = $printer;
        $this->astMapFactory = $astMapFactory;
        $this->useCaseQuery = $useCaseQuery;
        $this->listenerQuery = $listenerQuery;
        $this->subscriberQuery = $subscriberQuery;
        $this->eventDispatcherQuery = $eventDispatcherQuery;
    }

    public function printContextMap(ContextMap $contextMap, string $outFile, string $titleFontSize): void
    {
        file_put_contents($outFile, $this->printer->printToImage($contextMap, $titleFontSize));
    }

    public function createFromPaths(
        string $contextMapTitle,
        string $useCaseRegex,
        string $subscriberRegex,
        ComponentPathDto ...$componentPathList
    ): ContextMap {
        $componentList = [];
        foreach ($componentPathList as $componentPath) {
            $componentAstMap = $componentPath->isDir()
                ? $this->astMapFactory->constructFromFolder($componentPath->getPath())
                : $this->astMapFactory->constructFromFile($componentPath->getPath());

            $componentList[] = new Component(
                $componentPath->getComponentName(),
                $this->useCaseQuery->queryAst($componentAstMap, $useCaseRegex),
                $this->listenerQuery->queryAst($componentAstMap),
                $this->subscriberQuery->queryAst($componentAstMap, $subscriberRegex),
                $this->eventDispatcherQuery->queryAst($componentAstMap)
            );
        }

        return ContextMap::construct($contextMapTitle)->addComponents(...$componentList);
    }
}
