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

namespace Hgraca\AppMapper\Infrastructure\Printer\Graphviz;

use Fhaculty\Graph\Graph;
use Graphp\GraphViz\GraphViz;
use Hgraca\AppMapper\Core\Component\Main\Domain\AppMap;
use Hgraca\AppMapper\Core\Component\Main\Domain\Component;
use Hgraca\AppMapper\Core\Component\Main\Domain\Node\DomainNodeInterface;
use Hgraca\AppMapper\Core\Component\Main\Domain\Node\EventDispatcherNode;
use Hgraca\AppMapper\Core\Port\Configuration\Configuration;
use Hgraca\AppMapper\Core\Port\Logger\StaticLoggerFacade;
use Hgraca\AppMapper\Core\Port\Printer\PrinterInterface;
use Hgraca\PhpExtension\String\ClassService;
use Hgraca\PhpExtension\String\StringService;

final class GraphvizPrinter implements PrinterInterface
{
    public function printToImage(AppMap $appMap, Configuration $config): string
    {
        return $this->createImageData($this->printToDot($appMap, $config), $config->getOutputFileFormat());
    }

    public function printToDot(AppMap $appMap, Configuration $config): string
    {
        return $this->forceEdgesForward(
            (new GraphViz())->createScript($this->printAppmap($appMap, $config))
        );
    }

    public function printToHtml(AppMap $appMap, Configuration $config): string
    {
        $format = $config->getOutputFileFormat();

        $format = ($format === 'svg' || $format === 'svgz') ? 'svg+xml' : $format;

        $imgSrc = 'data:image/' . $format . ';base64,'
            . base64_encode(
                $this->createImageData(
                    $this->printToDot($appMap, $config),
                    $config->getOutputFileFormat()
                )
            );

        if ($format === 'svg' || $format === 'svgz') {
            return '<object type="image/svg+xml" data="' . $imgSrc . '"></object>';
        }

        return '<img src="' . $imgSrc . '" />';
    }

    private function forceEdgesForward(string $dot): string
    {
        return preg_replace('/dir *= *"?none"?/', 'dir=forward', $dot);
    }

    private function createImageData(string $dot, string $format): string
    {
        $dotTmpFile = tempnam(sys_get_temp_dir(), 'graphviz');
        if ($dotTmpFile === false) {
            throw new GraphvizException('Unable to get temporary file name for graphviz script');
        }

        if (file_put_contents($dotTmpFile, $dot, LOCK_EX) === false) {
            throw new GraphvizException('Unable to write graphviz script to temporary file');
        }

        $ret = 0;

        $executable = (new GraphViz())->getExecutable();
        $imageTmpFile = $dotTmpFile . '.' . $format;
        system(
            escapeshellarg($executable)
            . ' -T ' . escapeshellarg($format)
            . ' ' . escapeshellarg($dotTmpFile)
            . ' -o ' . escapeshellarg($imageTmpFile),
            $ret
        );
        if ($ret !== 0) {
            throw new GraphvizException(
                'Unable to invoke "' . $executable . '" to create image file (code ' . $ret . ')'
            );
        }
        unlink($dotTmpFile);

        $data = file_get_contents($imageTmpFile);
        unlink($imageTmpFile);

        return $data;
    }

    private function printAppmap(AppMap $appMap, Configuration $config): Graph
    {
        $graph = new Graph();
        $graph->setAttribute('graphviz.graph.layout', $config->isUseHtml() ? 'fdp' : 'sfdp');
        $graph->setAttribute('graphviz.graph.rankdir', 'LR');
        $graph->setAttribute('graphviz.graph.labelloc', 't'); // label location in the top
        $graph->setAttribute('graphviz.graph.label', $appMap->getName());
        $graph->setAttribute('graphviz.graph.fontname', 'arial');
        $graph->setAttribute('graphviz.graph.fontsize', $config->getTitleFontSize());
        $graph->setAttribute('graphviz.graph.nodesep', '4'); // the higher, the curvier the lines
        $graph->setAttribute('graphviz.graph.splines', 'true'); // for rounded edges around nodes
        $graph->setAttribute('graphviz.graph.overlap', 'false'); // so edges don't overlap nodes

        $graph->setAttribute('graphviz.node.fontname', 'arial');

        $this->addVertexesToGraph($graph, $appMap, $config);

        // Only after adding all components, can we start adding the edges (links)
        $this->addEdgesToGraph($graph, $appMap, $config);

        $this->addLegendToGraph($graph, $config);

        return $graph;
    }

    private function addVertexesToGraph(Graph $graph, AppMap $appMap, Configuration $config): void
    {
        foreach ($appMap->getComponentList() as $component) {
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

    private function addEdgesToGraph(Graph $graph, AppMap $appMap, Configuration $config): void
    {
        StaticLoggerFacade::debug('EDGES');
        StaticLoggerFacade::debug('=============================');
        foreach ($appMap->getComponentList() as $component) {
            foreach ($component->getEventDispatcherCollection() as $eventDispatcher) {
                $originComponentVertex = $graph->getVertex($component->getName());
                foreach ($appMap->getListenersOf($eventDispatcher) as $listener) {
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
