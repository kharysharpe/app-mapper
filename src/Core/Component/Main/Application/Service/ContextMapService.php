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

use Hgraca\ContextMapper\Core\Component\Main\Domain\Component;
use Hgraca\ContextMapper\Core\Component\Main\Domain\ContextMap;
use Hgraca\ContextMapper\Core\Component\Main\Domain\DomainAstMap;
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

    public function __construct(
        PrinterInterface $printer,
        AstMapFactoryInterface $astMapFactory
    ) {
        $this->printer = $printer;
        $this->astMapFactory = $astMapFactory;
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
        $useCaseCollector = $config->getUseCaseCollector();
        $listenerCollector = $config->getListenerCollector();
        $subscriberCollector = $config->getSubscriberCollector();
        $eventDispatcherCollector = $config->getEventDispatcherCollector();

        $completeAstMap = $this->astMapFactory->constructFromComponentDtoList(
            ...array_values($config->getComponents())
        );

        $componentList = [];
        foreach ($config->getComponents() as $componentDto) {
            $componentList[] = new Component(
                $componentDto->getName(),
                new DomainAstMap(
                    $completeAstMap,
                    $useCaseCollector,
                    $listenerCollector,
                    $subscriberCollector,
                    $eventDispatcherCollector
                )
            );
        }

        return ContextMap::construct($config->getTitle(), ...$componentList);
    }
}
