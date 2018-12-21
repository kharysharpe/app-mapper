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

namespace Hgraca\AppMapper\Core\Component\Main\Application\Service;

use Hgraca\AppMapper\Core\Component\Main\Domain\AppMap;
use Hgraca\AppMapper\Core\Component\Main\Domain\Component;
use Hgraca\AppMapper\Core\Component\Main\Domain\DomainAstMap;
use Hgraca\AppMapper\Core\Port\Configuration\Configuration;
use Hgraca\AppMapper\Core\Port\Parser\AstMapFactoryInterface;
use Hgraca\AppMapper\Core\Port\Printer\PrinterInterface;

final class AppMapService
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

    public function printAppmap(AppMap $appMap, Configuration $config): void
    {
        file_put_contents(
            $config->getOutputFileAbsPath(),
            $this->printer->printToImage($appMap, $config)
        );
    }

    public function createFromConfig(Configuration $config): AppMap
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

        return AppMap::construct($config->getTitle(), ...$componentList);
    }
}
