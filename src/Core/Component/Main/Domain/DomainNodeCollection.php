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

namespace Hgraca\AppMapper\Core\Component\Main\Domain;

use Hgraca\AppMapper\Core\Component\Main\Domain\Node\DomainNodeInterface;
use Hgraca\PhpExtension\Collection\Collection;

final class DomainNodeCollection extends Collection
{
    /** @noinspection MagicMethodsValidityInspection */

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(DomainNodeInterface ...$nodeList)
    {
        $this->addNodes(...$nodeList);
    }

    public function hasNodeWithFqcn(string $fqcn): bool
    {
        return array_key_exists($fqcn, $this->itemList);
    }

    public function addNodes(DomainNodeInterface ...$nodeList): void
    {
        foreach ($nodeList as $node) {
            $this->itemList[$node->getFullyQualifiedName()] = $node;
        }
    }
}
