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

namespace Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser;

final class Query
{
    /** @var callable[] */
    private $filterList = [];

    private $singleResult = false;

    private $componentName = '';

    public function addComponentFilter(string $componentName): void
    {
        $this->componentName = $componentName;
    }

    public function addFilter(callable $filter): void
    {
        $this->filterList[] = $filter;
    }

    public function getComponentFilter(): string
    {
        return $this->componentName;
    }

    /**
     * @return callable[]
     */
    public function getFilterList(): array
    {
        return $this->filterList;
    }

    public function returnSingleResult(): void
    {
        $this->singleResult = true;
    }

    public function shouldReturnSingleResult(): bool
    {
        return $this->singleResult;
    }
}
