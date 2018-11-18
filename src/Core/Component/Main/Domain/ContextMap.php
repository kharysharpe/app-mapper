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

namespace Hgraca\ContextMapper\Core\Component\Main\Domain;

final class ContextMap
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var Component[]
     */
    private $componentList = [];

    /**
     * @var EventNode[]
     */
    private $eventList = [];

    private function __construct(string $name)
    {
        $this->name = $name;
    }

    public static function construct(string $name): self
    {
        return new self($name);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function addComponents(Component ...$componentList): self
    {
        $this->componentList = array_merge($this->componentList, $componentList);

        return $this;
    }

    /**
     * @return Component[]
     */
    public function getComponentList(): array
    {
        return $this->componentList;
    }

    public function addEvents(EventNode ...$eventList): self
    {
        array_merge($this->eventList, $eventList);

        return $this;
    }
}
