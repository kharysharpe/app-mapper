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

use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Ast;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitorAbstract;

class AstConnectorVisitor extends NodeVisitorAbstract
{
    /**
     * @var Ast
     */
    private $ast;

    public function __construct(Ast $ast)
    {
        $this->ast = $ast;
    }

    public function enterNode(Node $node): void
    {
        switch (true) {
            case $node instanceof StaticCall:
                $class = $node->class;
                if (!$class instanceof Name) {
                    return;
                }
                $this->injectAstInNameNode($class);
                break;
            case $node instanceof ClassMethod:
                $returnType = $node->getReturnType();
                if (!$returnType instanceof Name) {
                    return;
                }
                $this->injectAstInReturnTypeNameNode($node, $returnType);
                break;
        }
    }

    private function injectAstInNameNode(Name $nameNode): void
    {
        /** @var FullyQualified $name */
        $name = $nameNode->getAttribute('resolvedName');
        $fqcn = $name->toCodeString();
        if ($this->ast->hasAstNode($fqcn)) {
            $nameNode->setAttribute('ast', $this->ast->getAstNode($fqcn));
        }
    }

    private function injectAstInReturnTypeNameNode(ClassMethod $classMethod, Name $returnTypeNameNode): void
    {
        /** @var FullyQualified $name */
        $name = $returnTypeNameNode->getAttribute('resolvedName');
        $fqcn = $name->toCodeString();
        if ($fqcn === 'self') {
            $returnTypeNameNode->setAttribute('ast', $classMethod->getAttribute('parent'));
        } elseif ($this->ast->hasAstNode($fqcn)) {
            $returnTypeNameNode->setAttribute('ast', $this->ast->getAstNode($fqcn));
        }
    }
}
