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
use Hgraca\ContextMapper\Core\Port\Printer\PrinterInterface;

final class GraphvizPrinter implements PrinterInterface
{
    private const FORMAT = 'svg';

    public function printToImage(ContextMap $contextMap): string
    {
        return (new GraphViz())
            ->setFormat(self::FORMAT)
            ->createImageData($this->printContextMap($contextMap));
    }

    public function printToDot(ContextMap $contextMap): string
    {
        return (new GraphViz())->createScript($this->printContextMap($contextMap));
    }

    public function printToHtml(ContextMap $contextMap): string
    {
        return (new GraphViz())->createImageHtml($this->printContextMap($contextMap));
    }

    private function printContextMap(ContextMap $contextMap): Graph
    {
        $graph = new Graph();
        $graph->setAttribute('graphviz.graph.labelloc', 't');
        $graph->setAttribute('graphviz.graph.label', $contextMap->getName());
        $graph->setAttribute('graphviz.graph.fontname', 'arial');
        $graph->setAttribute('graphviz.graph.fontsize', '30');
        $graph->setAttribute('graphviz.graph.nodesep', '5');

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

        return $graph;
    }

    private function printComponent(Component $component): string
    {
        $componentStr = '<table border="0" cellborder="1" cellspacing="0">'
            . '<tr><td BGCOLOR="Lavender"><b>' . $component->getName() . '</b></td></tr>';

        foreach ($component->getUseCaseList() as $useCase) {
            $componentStr .= '<tr><td BGCOLOR="Lightsalmon" PORT="' . $useCase->getFullyQualifiedClassName() . '">'
                . $useCase->getCanonicalClassName()
                . '</td></tr>';
        }

        foreach ($component->getListenerList() as $listener) {
            $componentStr .= '<tr><td BGCOLOR="Honeydew" PORT="' . $listener->getFullyQualifiedName() . '">'
                . $listener->getCanonicalName()
                . '</td></tr>';
        }

        foreach ($component->getSubscriberList() as $subscriber) {
            $componentStr .= '<tr><td BGCOLOR="Lightcyan" PORT="' . $subscriber->getFullyQualifiedName() . '">'
                . $subscriber->getCanonicalName()
                . '</td></tr>';
        }

        $componentStr .= '</table>';

        return $componentStr;
    }
}
