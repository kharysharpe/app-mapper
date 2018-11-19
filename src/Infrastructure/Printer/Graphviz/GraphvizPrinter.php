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

    private $tmp = [];

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

        // Only after adding all components, can we start adding the edges (links)
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

        return $graph;
    }

    private function printComponent(Component $component): string
    {
        $componentStr = '<table border="0" cellborder="1" cellspacing="0">'
            . '<tr><td BGCOLOR="Lavender"><b>' . $component->getName() . '</b></td></tr>';

        foreach ($component->getUseCaseList() as $useCase) {
            $componentStr .= '<tr><td BGCOLOR="Lightsalmon" PORT="' . $this->createPortId($useCase) . '">'
                . $useCase->getCanonicalName()
                . '</td></tr>';
            $this->tmp[] = $useCase->getFullyQualifiedName();
        }

        foreach ($component->getListenerList() as $listener) {
            $componentStr .= '<tr><td BGCOLOR="Honeydew" PORT="' . $this->createPortId($listener) . '">'
                . $listener->getCanonicalName()
                . '</td></tr>';
            $this->tmp[] = $listener->getFullyQualifiedName();
        }

        foreach ($component->getSubscriberList() as $subscriber) {
            $componentStr .= '<tr><td BGCOLOR="Lightcyan" PORT="' . $this->createPortId($subscriber) . '">'
                . $subscriber->getCanonicalName()
                . '</td></tr>';
            $this->tmp[] = $subscriber->getFullyQualifiedName();
        }

        sort($this->tmp);

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
}
