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

namespace Hgraca\ContextMapper\Infrastructure\Printer\Graphviz;

use Fhaculty\Graph\Graph;
use Graphp\GraphViz\GraphViz;
use Hgraca\ContextMapper\Core\Component\Main\Domain\Component;
use Hgraca\ContextMapper\Core\Component\Main\Domain\ContextMap;
use Hgraca\ContextMapper\Core\Component\Main\Domain\Node\DomainNodeInterface;
use Hgraca\ContextMapper\Core\Component\Main\Domain\Node\EventDispatcherNode;
use Hgraca\ContextMapper\Core\Port\Configuration\Configuration;
use Hgraca\ContextMapper\Core\Port\Logger\StaticLoggerFacade;
use Hgraca\ContextMapper\Core\Port\Printer\PrinterInterface;
use Hgraca\PhpExtension\String\ClassService;
use Hgraca\PhpExtension\String\StringService;

final class GraphvizPrinter implements PrinterInterface
{
    public function printToImage(ContextMap $contextMap, Configuration $config): string
    {
        return (new GraphViz())
            ->setFormat($config->getOutputFileFormat())
            ->createImageData($this->printContextMap($contextMap, $config));
    }

    public function printToDot(ContextMap $contextMap, Configuration $config): string
    {
        return (new GraphViz())->createScript($this->printContextMap($contextMap, $config));
    }

    public function printToHtml(ContextMap $contextMap, Configuration $config): string
    {
        return (new GraphViz())->createImageHtml($this->printContextMap($contextMap, $config));
    }

    private function printContextMap(ContextMap $contextMap, Configuration $config): Graph
    {
        $graph = new Graph();
        $graph->setAttribute('graphviz.graph.layout', $config->isUseHtml() ? 'fdp' : 'sfdp');
        $graph->setAttribute('graphviz.graph.rankdir', 'LR');
        $graph->setAttribute('graphviz.graph.labelloc', 't'); // label location in the top
        $graph->setAttribute('graphviz.graph.label', $contextMap->getName());
        $graph->setAttribute('graphviz.graph.fontname', 'arial');
        $graph->setAttribute('graphviz.graph.fontsize', $config->getTitleFontSize());
        $graph->setAttribute('graphviz.graph.nodesep', '2'); // without this, we get straight lines
        $graph->setAttribute('graphviz.graph.splines', 'true'); // for rounded edges around nodes

        $graph->setAttribute('graphviz.node.fontname', 'arial');

        $this->addVertexesToGraph($graph, $contextMap, $config);

        // Only after adding all components, can we start adding the edges (links)
        $this->addEdgesToGraph($graph, $contextMap, $config);

        $this->addLegendToGraph($graph, $config);

        return $graph;
    }

    private function addVertexesToGraph(Graph $graph, ContextMap $contextMap, Configuration $config): void
    {
        foreach ($contextMap->getComponentList() as $component) {
            $graphComponent = $graph->createVertex($component->getName());
            $graphComponent->setAttribute(
                'graphviz.pos',
                $config->getComponentPositionX($component->getName())
                . ','
                . $config->getComponentPositionY($component->getName())
                . '!'
            );

            if ($config->isUseHtml()) {
                $graphComponent->setAttribute('graphviz.shape', 'none');
                $graphComponent->setAttribute(
                    'graphviz.label',
                    GraphViz::raw(
                        '<' . $this->printComponentUsingHtml($component, $config) . '>'
                    )
                );
            } else {
                $graphComponent->setAttribute('graphviz.shape', 'record');
                $graphComponent->setAttribute(
                    'graphviz.label',
                    GraphViz::raw(
                        '"' . $this->printComponent($component) . '"'
                    )
                );
            }
        }
    }

    private function printComponent(Component $component): string
    {
        StaticLoggerFacade::debug($component->getName());
        StaticLoggerFacade::debug('VERTICES');
        StaticLoggerFacade::debug('=============================');
        $componentPieces[] = '== ' . mb_strtoupper($component->getName()) . ' == ';

        StaticLoggerFacade::debug('-----------------------------');
        foreach ($component->getUseCaseCollection() as $useCase) {
            $componentPieces[] = "<{$this->createPortId($useCase)}> * {$useCase->getCanonicalName()}";
            StaticLoggerFacade::debug($this->createPortId($useCase) . ' | ' . $useCase->getCanonicalName());
        }

        StaticLoggerFacade::debug('-----------------------------');
        foreach ($component->getPartialUseCaseCollection() as $partialUseCase) {
            $componentPieces[] = "<{$this->createPortId($partialUseCase)}> + {$partialUseCase->getCanonicalName()}";
            StaticLoggerFacade::debug(
                $this->createPortId($partialUseCase) . ' | ' . $partialUseCase->getCanonicalName()
            );
        }

        StaticLoggerFacade::debug('-----------------------------');
        foreach ($component->getListenerCollection() as $listener) {
            $componentPieces[] = "<{$this->createPortId($listener)}> - {$listener->getCanonicalName()}";
            StaticLoggerFacade::debug($this->createPortId($listener) . ' | ' . $listener->getCanonicalName());
        }

        StaticLoggerFacade::debug('-----------------------------');
        foreach ($component->getSubscriberCollection() as $subscriber) {
            $componentPieces[] = "<{$this->createPortId($subscriber)}> = {$subscriber->getCanonicalName()}";
            StaticLoggerFacade::debug($this->createPortId($subscriber) . ' | ' . $subscriber->getCanonicalName());
        }
        StaticLoggerFacade::debug('=============================');

        return implode('|', $componentPieces);
    }

    private function printComponentUsingHtml(Component $component, Configuration $config): string
    {
        StaticLoggerFacade::debug($component->getName());
        StaticLoggerFacade::debug('VERTICES');
        StaticLoggerFacade::debug('=============================');
        $componentStr = '<table border="0" cellborder="1" cellspacing="0">'
            . '<tr><td BGCOLOR="' . $config->getComponentColor() . '"><b>' . $component->getName() . '</b></td></tr>';

        StaticLoggerFacade::debug('-----------------------------');
        foreach ($component->getUseCaseCollection() as $useCase) {
            $componentStr .=
                '<tr><td BGCOLOR="' . $config->getUseCaseColor()
                . '" PORT="' . $this->createPortId($useCase) . '">'
                . $useCase->getCanonicalName()
                . '</td></tr>';
            StaticLoggerFacade::debug($this->createPortId($useCase) . ' | ' . $useCase->getCanonicalName());
        }

        StaticLoggerFacade::debug('-----------------------------');
        foreach ($component->getPartialUseCaseCollection() as $partialUseCase) {
            $componentStr .=
                '<tr><td BGCOLOR="' . $config->getPartialUseCaseColor()
                . '" PORT="' . $this->createPortId($partialUseCase) . '">'
                . $partialUseCase->getCanonicalName()
                . '</td></tr>';
            StaticLoggerFacade::debug(
                $this->createPortId($partialUseCase) . ' | ' . $partialUseCase->getCanonicalName()
            );
        }

        StaticLoggerFacade::debug('-----------------------------');
        foreach ($component->getListenerCollection() as $listener) {
            $componentStr .=
                '<tr><td BGCOLOR="' . $config->getListenerColor()
                . '" PORT="' . $this->createPortId($listener) . '">'
                . $listener->getCanonicalName()
                . '</td></tr>';
            StaticLoggerFacade::debug($this->createPortId($listener) . ' | ' . $listener->getCanonicalName());
        }

        StaticLoggerFacade::debug('-----------------------------');
        foreach ($component->getSubscriberCollection() as $subscriber) {
            $componentStr .=
                '<tr><td BGCOLOR="' . $config->getSubscriberColor()
                . '" PORT="' . $this->createPortId($subscriber) . '">'
                . $subscriber->getCanonicalName()
                . '</td></tr>';
            StaticLoggerFacade::debug($this->createPortId($subscriber) . ' | ' . $subscriber->getCanonicalName());
        }
        StaticLoggerFacade::debug('=============================');

        $componentStr .= '</table>';

        return $componentStr;
    }

    private function createPortId(DomainNodeInterface $node): string
    {
        $fqn = $node->getFullyQualifiedName();
        $id = StringService::replace('::', '.', $fqn);
        $id = StringService::replace('\\', '.', $id);
        $id = $node instanceof EventDispatcherNode
            ? mb_substr($id, 0, mb_strrpos($id, '.'))
            : $id;
        $id = ltrim($id, '.');
        $useCaseTermination = 'Handler.';
        StaticLoggerFacade::notice(
            "We currently assume the use cases FQN contains '$useCaseTermination'.\n"
            . "We need to make this more flexible.\n",
            [__METHOD__]
        );
        if (StringService::contains($useCaseTermination, $id)) {
            $id = mb_substr($id, 0, mb_strrpos($id, $useCaseTermination) + mb_strlen($useCaseTermination) - 1);
        }

        return $id;
    }

    private function addEdgesToGraph(Graph $graph, ContextMap $contextMap, Configuration $config): void
    {
        StaticLoggerFacade::debug('EDGES');
        StaticLoggerFacade::debug('=============================');
        foreach ($contextMap->getComponentList() as $component) {
            foreach ($component->getEventDispatcherCollection() as $eventDispatcher) {
                $originComponentVertex = $graph->getVertex($component->getName());
                foreach ($contextMap->getListenersOf($eventDispatcher) as $listener) {
                    $destinationComponentVertex = $graph->getVertex($listener->getComponent()->getName());
                    $eventEdge = $originComponentVertex->createEdgeTo($destinationComponentVertex);
                    $eventEdge->setAttribute('graphviz.tailport', $this->createPortId($eventDispatcher));
                    $eventEdge->setAttribute('graphviz.headport', $this->createPortId($listener));
                    $eventEdge->setAttribute('graphviz.dir', 'forward'); // force the edge direction [DOESN'T WORK]
                    $eventEdge->setAttribute('graphviz.style', $config->getEventLine());
                    $eventEdge->setAttribute('graphviz.color', $config->getEventColor());
                    $eventEdge->setAttribute(
                        'graphviz.xlabel',
                        ClassService::extractCanonicalClassName($listener->getListenedFqcn())
                    );
                    $eventEdge->setAttribute('graphviz.fontname', 'arial');
                    StaticLoggerFacade::debug(
                        $this->createPortId($eventDispatcher) . ' --> ' . $this->createPortId($listener)
                    );
                }
            }
        }
    }

    private function addLegendToGraph(Graph $graph, Configuration $config): void
    {
        $legendVertex = $graph->createVertex('Legend');
        $legendVertex->setAttribute('graphviz.shape', 'none');
        $legendVertex->setAttribute(
            'graphviz.pos',
            $config->getLegendPositionX() . ',' . $config->getLegendPositionY() . '!'
        );

        $legendTable = '<table border="1" cellborder="1" cellspacing="0">'
            . '<tr><td border="0"><b>' . $legendVertex->getId() . '</b></td></tr>'
            . '<tr><td border="0"> &nbsp; </td></tr>'
            . '<tr><td border="0" BGCOLOR="' . $config->getComponentColor() . '"><b> Component </b></td></tr>'
            . '<tr><td border="0" BGCOLOR="' . $config->getUseCaseColor() . '"> Use Case </td></tr>'
            . '<tr><td border="0" BGCOLOR="' . $config->getPartialUseCaseColor() . '"> Partial Use Case </td></tr>'
            . '<tr><td border="0" BGCOLOR="' . $config->getListenerColor() . '"> Listener </td></tr>'
            . '<tr><td border="0" BGCOLOR="' . $config->getSubscriberColor() . '"> Subscriber </td></tr>'
            . '<tr><td border="0" BGCOLOR="' . $config->getEventColor() . '">Event</td></tr>'
            . '</table>';

        $legendVertex->setAttribute(
            'graphviz.label',
            GraphViz::raw(
                '<' . $legendTable . '>'
            )
        );
    }
}
