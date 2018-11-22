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
use Hgraca\ContextMapper\Core\Component\Main\Domain\DomainNodeInterface;
use Hgraca\ContextMapper\Core\Port\Configuration\Configuration;
use Hgraca\ContextMapper\Core\Port\Printer\PrinterInterface;
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
        $graph->setAttribute('graphviz.graph.layout', 'fdp');
        $graph->setAttribute('graphviz.graph.rankdir', 'LR');
        $graph->setAttribute('graphviz.graph.labelloc', 't'); // label location in the top
        $graph->setAttribute('graphviz.graph.label', $contextMap->getName());
        $graph->setAttribute('graphviz.graph.fontname', 'arial');
        $graph->setAttribute('graphviz.graph.fontsize', $config->getTitleFontSize());
        $graph->setAttribute('graphviz.graph.nodesep', '2'); // without this, we get straight lines

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
            $graphComponent->setAttribute('graphviz.shape', 'none');
            $graphComponent->setAttribute(
                'graphviz.pos',
                $config->getComponentPositionX($component->getName())
                . ','
                . $config->getComponentPositionY($component->getName())
                . '!'
            );
            $graphComponent->setAttribute(
                'graphviz.label',
                GraphViz::raw(
                    '<' . $this->printComponent($component, $config) . '>'
                )
            );
        }
    }

    private function printComponent(Component $component, Configuration $config): string
    {
        $componentStr = '<table border="0" cellborder="1" cellspacing="0">'
            . '<tr><td BGCOLOR="' . $config->getComponentColor() . '"><b>' . $component->getName() . '</b></td></tr>';

        foreach ($component->getUseCaseList() as $useCase) {
            $componentStr .=
                '<tr><td BGCOLOR="' . $config->getUseCaseColor() . '" PORT="' . $this->createPortId($useCase) . '">'
                . $useCase->getCanonicalName()
                . '</td></tr>';
        }

        foreach ($component->getListenerList() as $listener) {
            $componentStr .=
                '<tr><td BGCOLOR="' . $config->getListenerColor() . '" PORT="' . $this->createPortId($listener) . '">'
                . $listener->getCanonicalName()
                . '</td></tr>';
        }

        foreach ($component->getSubscriberList() as $subscriber) {
            $componentStr .=
                '<tr><td BGCOLOR="' . $config->getSubscriberColor() . '" PORT="' . $this->createPortId(
                    $subscriber
                ) . '">'
                . $subscriber->getCanonicalName()
                . '</td></tr>';
        }

        $componentStr .= '</table>';

        return $componentStr;
    }

    private function createPortId(DomainNodeInterface $node): string
    {
        $id = StringService::replace('::', '.', $node->getFullyQualifiedName());
        $id = StringService::replace('\\', '.', $id);
        $id = ltrim($id, '.');
        $useCaseTermination = 'Handler.'; // TODO refactor this in a more flexible way
        if (StringService::contains($useCaseTermination, $id)) {
            $id = mb_substr($id, 0, mb_strrpos($id, $useCaseTermination) + mb_strlen($useCaseTermination) - 1);
        }
        $listenerTermination = 'Listener.'; // TODO refactor this in a more flexible way
        if (StringService::contains($listenerTermination, $id)) {
            $id = mb_substr($id, 0, mb_strrpos($id, $listenerTermination) + mb_strlen($listenerTermination) - 1);
        }
        $id = StringService::replaceFromEnd('Handler', 'Command', $id);

        return $id;
    }

    private function addEdgesToGraph(Graph $graph, ContextMap $contextMap, Configuration $config): void
    {
        foreach ($contextMap->getComponentList() as $component) {
            foreach ($component->getEventDispatchingList() as $eventDispatching) {
                $originComponentVertex = $graph->getVertex($component->getName());
                foreach ($contextMap->getListenersOf($eventDispatching) as $listener) {
                    $destinationComponentVertex = $graph->getVertex($listener->getComponent()->getName());
                    $eventEdge = $originComponentVertex->createEdgeTo($destinationComponentVertex);
                    $eventEdge->setAttribute('graphviz.tailport', $this->createPortId($eventDispatching));
                    $eventEdge->setAttribute('graphviz.headport', $this->createPortId($listener));
                    $eventEdge->setAttribute('graphviz.dir', 'forward'); // force the edge direction
                    $eventEdge->setAttribute('graphviz.style', $config->getEventLine());
                    $eventEdge->setAttribute('graphviz.color', $config->getEventColor());
                    $eventEdge->setAttribute('graphviz.xlabel', $eventDispatching->getEventCanonicalName());
                    $eventEdge->setAttribute('graphviz.fontname', 'arial');
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

        $legendTable = '<table border="0" cellborder="1" cellspacing="0">'
            . '<tr><td BGCOLOR="Gray"><b>' . $legendVertex->getId() . '</b></td></tr>'
            . '<tr><td BGCOLOR="' . $config->getComponentColor() . '"><b> Component </b></td></tr>'
            . '<tr><td BGCOLOR="' . $config->getUseCaseColor() . '"> Use Case </td></tr>'
            . '<tr><td BGCOLOR="' . $config->getListenerColor() . '"> Listener </td></tr>'
            . '<tr><td BGCOLOR="' . $config->getSubscriberColor() . '"> Subscriber </td></tr>';
        // TODO add events style

        $legendTable .= '</table>';

        $legendVertex->setAttribute(
            'graphviz.label',
            GraphViz::raw(
                '<' . $legendTable . '>'
            )
        );
    }
}
