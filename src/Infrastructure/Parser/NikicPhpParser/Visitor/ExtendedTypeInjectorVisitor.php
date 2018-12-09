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

namespace Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Visitor;

use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\NodeCollection;
use PhpParser\Node;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\NodeVisitorAbstract;

class ExtendedTypeInjectorVisitor extends NodeVisitorAbstract implements AstConnectorVisitorInterface
{
    /**
     * @var NodeCollection
     */
    private $ast;

    public function __construct(NodeCollection $ast)
    {
        $this->ast = $ast;
    }

    public function enterNode(Node $node): void
    {
        switch (true) {
            case $node instanceof Class_ && $node->extends !== null:
                $this->addType($node->extends);
                break;
            case $node instanceof Interface_ && !empty($node->extends):
                foreach ($node->extends as $parent) {
                    $this->addType($parent);
                }
                break;
        }
    }

    /**
     * @param Node|Class_|Interface_ $node
     */
    private function addType(Node $node): void
    {
        /** @var FullyQualified $fullyQualified */
        $fullyQualified = $node->getAttribute('resolvedName');
        $fqcn = $fullyQualified->toCodeString();

        $node->setAttribute(
            Type::getName(),
            new Type(
                $fqcn,
                $this->ast->hasAstNode($fqcn) ? $this->ast->getAstNode($fqcn) : null
            )
        );
    }
}
