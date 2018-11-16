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

use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\AstMap;
use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\NodeVisitorAbstract;

class InstantiationTypeInjectorVisitor extends NodeVisitorAbstract implements AstConnectorVisitorInterface
{
    /**
     * @var AstMap
     */
    private $ast;

    public function __construct(AstMap $ast)
    {
        $this->ast = $ast;
    }

    public function enterNode(Node $node): void
    {
        if (!$node instanceof New_) {
            return;
        }
        /** @var FullyQualified $name */
        $name = $node->class->getAttribute('resolvedName');
        $fqcn = $name->toCodeString();
        $node->class->setAttribute(
            self::AST_KEY,
            $this->ast->hasAstNode($fqcn)
                ? $this->ast->getAstNode($fqcn)
                : $fqcn
        );
    }
}
