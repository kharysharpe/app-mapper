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

namespace Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use function count;

class ParentConnectorVisitor extends NodeVisitorAbstract
{
    private const PARENT_NODE = 'parentNode';

    private $stack;

    public function beforeTraverse(array $nodes): void
    {
        $this->stack = [];
    }

    public function enterNode(Node $node): void
    {
        if (!empty($this->stack)) {
            $node->setAttribute(self::PARENT_NODE, $this->stack[count($this->stack) - 1]);
        }
        $this->stack[] = $node;
    }

    public function leaveNode(Node $node): void
    {
        array_pop($this->stack);
    }

    public static function getParentNode(Node $node): Node
    {
        return $node->getAttribute(self::PARENT_NODE);
    }

    public static function getFirstParentNodeOfType(Node $node, string $type): Node
    {
        do {
            $node = self::getParentNode($node);
        } while (!$node instanceof $type);

        return $node;
    }
}
