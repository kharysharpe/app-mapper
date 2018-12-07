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

namespace Hgraca\ContextMapper\Core\Port\Configuration;

use Hgraca\ContextMapper\Core\Port\Configuration\Collector\CodeUnitCollector;

class Configuration
{
    /**
     * @var array
     */
    private $paths;

    /**
     * @var array
     */
    private $includeFiles;

    /**
     * @var string
     */
    private $outFile;

    /**
     * @var array
     */
    private $title;

    /**
     * @var array
     */
    private $legend;

    /**
     * @var array
     */
    private $codeUnits;

    /**
     * @var array
     */
    private $components = [];

    public function __construct(
        array $paths,
        array $includeFiles,
        string $outFile,
        array $title,
        array $legend,
        array $codeUnits,
        array $componentList
    ) {
        $this->paths = $paths;
        $this->includeFiles = $includeFiles;
        $this->outFile = $outFile;
        $this->title = $title;
        $this->legend = $legend;
        $this->codeUnits = $codeUnits;

        foreach ($componentList as $component) {
            $componentName = $component['name'];
            $this->components[$componentName] = new ComponentDto(
                $componentName,
                $component['path'],
                $component['position']['x'],
                $component['position']['y']
            );
        }
    }

    /**
     * @return string[]
     */
    public function getPaths(): array
    {
        return $this->paths;
    }

    /**
     * @return string[]
     */
    public function getIncludeFiles(): array
    {
        return $this->includeFiles;
    }

    public function getOutputFileAbsPath(): string
    {
        return $this->outFile;
    }

    public function getOutputFileFormat(): string
    {
        return mb_substr($this->outFile, mb_strrpos($this->outFile, '.') + 1);
    }

    public function getTitle(): string
    {
        return $this->title['text'];
    }

    public function getTitleFontSize(): int
    {
        return $this->title['font_size'];
    }

    public function getLegendPositionX(): int
    {
        return $this->legend['position']['x'];
    }

    public function getLegendPositionY(): int
    {
        return $this->legend['position']['y'];
    }

    /**
     * @return ComponentDto[]
     */
    public function getComponents(): array
    {
        return $this->components;
    }

    public function getUseCaseCollector(): CodeUnitCollector
    {
        return CodeUnitCollector::constructFromCollector($this->codeUnits['use_case']['collector']);
    }

    public function getListenerCollector(): CodeUnitCollector
    {
        return CodeUnitCollector::constructFromCollector($this->codeUnits['listener']['collector']);
    }

    public function getSubscriberCollector(): CodeUnitCollector
    {
        return CodeUnitCollector::constructFromCollector($this->codeUnits['subscriber']['collector']);
    }

    public function getEventDispatcherCollector(): CodeUnitCollector
    {
        return CodeUnitCollector::constructFromCollector($this->codeUnits['event']['collector']);
    }

    public function getComponentColor(): string
    {
        return $this->codeUnits['component']['color'];
    }

    public function getUseCaseColor(): string
    {
        return $this->codeUnits['use_case']['color'];
    }

    public function getListenerColor(): string
    {
        return $this->codeUnits['listener']['color'];
    }

    public function getSubscriberColor(): string
    {
        return $this->codeUnits['subscriber']['color'];
    }

    public function getEventColor(): string
    {
        return $this->codeUnits['event']['color'];
    }

    public function getEventLine(): string
    {
        return $this->codeUnits['event']['line'];
    }

    public function getComponent(string $name): ComponentDto
    {
        return $this->components[$name];
    }

    public function getComponentPositionX(string $name): int
    {
        return $this->getComponent($name)->getLocationX();
    }

    public function getComponentPositionY(string $name): int
    {
        return $this->getComponent($name)->getLocationY();
    }
}
