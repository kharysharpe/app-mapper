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

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\FindingVisitor;
use PhpParser\NodeVisitor\FirstFindingVisitor;

final class NodeFinder
{
    public function find(callable $filter, Node ...$nodes): array
    {
        $traverser = new NodeTraverser();
        $visitor = new FindingVisitor($filter);
        $traverser->addVisitor($visitor);
        $traverser->traverse($nodes);

        return $visitor->getFoundNodes();
    }

    public function findFirst(callable $filter, Node ...$nodes): ?Node
    {
        $traverser = new NodeTraverser();
        $visitor = new FirstFindingVisitor($filter);
        $traverser->addVisitor($visitor);
        $traverser->traverse($nodes);

        return $visitor->getFoundNode();
    }
}
