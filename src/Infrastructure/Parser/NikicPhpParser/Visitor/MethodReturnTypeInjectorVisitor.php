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
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitorAbstract;

class MethodReturnTypeInjectorVisitor extends NodeVisitorAbstract implements AstConnectorVisitorInterface
{
    /**
     * @var NodeCollection
     */
    private $astMap;

    public function __construct(NodeCollection $astMap)
    {
        $this->astMap = $astMap;
    }

    public function enterNode(Node $method): void
    {
        if (!$method instanceof ClassMethod) {
            return;
        }
        $returnType = $method->getReturnType();
        if ($returnType instanceof NullableType) {
            $returnType = $returnType->type;
        }
        switch (true) {
            case $returnType === null:
                return;
            case $returnType instanceof Identifier:
                $returnType->setAttribute(self::KEY_AST, $returnType->name);
                break;
            default:
                $returnType->setAttribute(self::KEY_AST, $this->resolveReturnTypeNode($method));
        }
    }

    private function getMethodClassNode(ClassMethod $method): Node
    {
        return $method->getAttribute('parent');
    }

    private function resolveReturnTypeNode(ClassMethod $method)
    {
        $returnType = $method->getReturnType();
        if ($returnType instanceof NullableType) {
            $returnType = $returnType->type;
        }
        /** @var FullyQualified $name */
        $name = $returnType->getAttribute('resolvedName');
        $fqcn = $name->toCodeString();

        switch (true) {
            case $fqcn === 'self':
                return $this->getMethodClassNode($method);
            case $this->astMap->hasAstNode($fqcn):
                return $this->astMap->getAstNode($fqcn);
            default:
                return $fqcn;
        }
    }
}
