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
use Hgraca\ContextMapper\Core\Port\Printer\PrinterInterface;
use Hgraca\PhpExtension\String\StringService;

final class GraphvizPrinter implements PrinterInterface
{
    private const FORMAT = 'svg';
    private const COLOR_COMPONENT = 'Lavender';
    private const COLOR_USE_CASE = 'Lightsalmon';
    private const COLOR_LISTENER = 'Honeydew';
    private const COLOR_SUBSCRIBER = 'Lightcyan';

    public function printToImage(ContextMap $contextMap, string $titleFontSize): string
    {
        return (new GraphViz())
            ->setFormat(self::FORMAT)
            ->createImageData($this->printContextMap($contextMap, $titleFontSize));
    }

    public function printToDot(ContextMap $contextMap, string $titleFontSize): string
    {
        return (new GraphViz())->createScript($this->printContextMap($contextMap, $titleFontSize));
    }

    public function printToHtml(ContextMap $contextMap, string $titleFontSize): string
    {
        return (new GraphViz())->createImageHtml($this->printContextMap($contextMap, $titleFontSize));
    }

    private function printContextMap(ContextMap $contextMap, string $titleFontSize): Graph
    {
        $graph = new Graph();
        $graph->setAttribute('graphviz.graph.layout', 'circo');
        $graph->setAttribute('graphviz.graph.rankdir', 'LR');
        $graph->setAttribute('graphviz.graph.labelloc', 't');
        $graph->setAttribute('graphviz.graph.label', $contextMap->getName());
        $graph->setAttribute('graphviz.graph.fontname', 'arial');
        $graph->setAttribute('graphviz.graph.fontsize', $titleFontSize);
//        $graph->setAttribute('graphviz.graph.ranksep', '1.5');
        $graph->setAttribute('graphviz.graph.nodesep', '2');
        $graph->setAttribute('graphviz.graph.size', '60,60');
        $graph->setAttribute('graphviz.graph.ratio', 'fill');

        $graph->setAttribute('graphviz.node.fontname', 'arial');

        $this->addVertexesToGraph($graph, $contextMap);

        // Only after adding all components, can we start adding the edges (links)
        $this->addEdgesToGraph($graph, $contextMap);

        $this->addLegendToGraph($graph);

        return $graph;
    }

    private function addVertexesToGraph(Graph $graph, ContextMap $contextMap): void
    {
        foreach ($contextMap->getComponentList() as $component) {
            $graphComponent = $graph->createVertex($component->getName());
            $graphComponent->setAttribute('graphviz.shape', 'none');
            $graphComponent->setAttribute(
                'graphviz.label',
                GraphViz::raw(
                    '<' . $this->printComponent($component) . '>'
                )
            );
        }
    }

    private function printComponent(Component $component): string
    {
        $componentStr = '<table border="0" cellborder="1" cellspacing="0">'
            . '<tr><td BGCOLOR="' . self::COLOR_COMPONENT . '"><b>' . $component->getName() . '</b></td></tr>';

        foreach ($component->getUseCaseList() as $useCase) {
            $componentStr .= '<tr><td BGCOLOR="' . self::COLOR_USE_CASE . '" PORT="' . $this->createPortId(
                    $useCase
                ) . '">'
                . $useCase->getCanonicalName()
                . '</td></tr>';
        }

        foreach ($component->getListenerList() as $listener) {
            $componentStr .= '<tr><td BGCOLOR="' . self::COLOR_LISTENER . '" PORT="' . $this->createPortId(
                    $listener
                ) . '">'
                . $listener->getCanonicalName()
                . '</td></tr>';
        }

        foreach ($component->getSubscriberList() as $subscriber) {
            $componentStr .= '<tr><td BGCOLOR="' . self::COLOR_SUBSCRIBER . '" PORT="' . $this->createPortId(
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
        $useCaseTermination = 'Handler.';
        if (StringService::contains($useCaseTermination, $id)) {
            $id = mb_substr($id, 0, mb_strrpos($id, $useCaseTermination) + mb_strlen($useCaseTermination) - 1);
        }
        $listenerTermination = 'Listener.';
        if (StringService::contains($listenerTermination, $id)) {
            $id = mb_substr($id, 0, mb_strrpos($id, $listenerTermination) + mb_strlen($listenerTermination) - 1);
        }
        $id = StringService::replaceFromEnd('Handler', 'Command', $id);

        return $id;
    }

    private function addEdgesToGraph(Graph $graph, ContextMap $contextMap): void
    {
        foreach ($contextMap->getComponentList() as $component) {
            foreach ($component->getEventDispatcherList() as $eventDispatcher) {
                $originComponentVertex = $graph->getVertex($component->getName());
                foreach ($contextMap->getListenersOf($eventDispatcher) as $listener) {
                    $destinationComponentVertex = $graph->getVertex($listener->getComponent()->getName());
                    $eventEdge = $originComponentVertex->createEdgeTo($destinationComponentVertex);
                    $eventEdge->setAttribute('graphviz.tailport', $this->createPortId($eventDispatcher));
                    $eventEdge->setAttribute('graphviz.headport', $this->createPortId($listener));
                    $eventEdge->setAttribute('graphviz.style', 'dashed');
                    $eventEdge->setAttribute('graphviz.xlabel', $eventDispatcher->getEventCanonicalName());
                    $eventEdge->setAttribute('graphviz.fontname', 'arial');
                }
            }
        }
    }

    private function addLegendToGraph(Graph $graph): void
    {
        $legendVertex = $graph->createVertex('Legend');
        $legendVertex->setAttribute('graphviz.rank', 'sink'); // put it at the bottom
        $legendVertex->setAttribute('graphviz.shape', 'none');

        $legendTable = '<table border="0" cellborder="1" cellspacing="0">'
            . '<tr><td BGCOLOR="Gray"><b>' . $legendVertex->getId() . '</b></td></tr>'
            . '<tr><td BGCOLOR="' . self::COLOR_COMPONENT . '"><b> Component </b></td></tr>'
            . '<tr><td BGCOLOR="' . self::COLOR_USE_CASE . '"> Use Case </td></tr>'
            . '<tr><td BGCOLOR="' . self::COLOR_LISTENER . '"> Listener </td></tr>'
            . '<tr><td BGCOLOR="' . self::COLOR_SUBSCRIBER . '"> Subscriber </td></tr>';

        $legendTable .= '</table>';

        $legendVertex->setAttribute(
            'graphviz.label',
            GraphViz::raw(
                '<' . $legendTable . '>'
            )
        );
    }
}
