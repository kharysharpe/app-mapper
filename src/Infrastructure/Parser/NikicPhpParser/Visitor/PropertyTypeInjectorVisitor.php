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

use Hgraca\ContextMapper\Core\Port\Parser\Exception\ParserException;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\AstMap;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Node\MethodCallAdapter;
use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeVisitorAbstract;
use function array_key_exists;

class PropertyTypeInjectorVisitor extends NodeVisitorAbstract implements AstConnectorVisitorInterface
{
    /**
     * @var AstMap
     */
    private $ast;

    private $propertyList = [];

    public function __construct(AstMap $ast)
    {
        $this->ast = $ast;
    }

    public function enterNode(Node $node): void
    {
        switch (true) {
            case $node instanceof PropertyFetch
                && $node->getAttribute('parent') instanceof Assign:
                // isPropertyAssignment
                /** @var Assign $assignment */
                $assignment = $node->getAttribute('parent');
                if (!$assignment->expr instanceof Variable) {
                    return; // TODO cases where a property is assigned the result of an expression
                }
                $node->setAttribute(
                    self::AST_KEY,
                    $assignment->expr->getAttribute(self::AST_KEY)
                );
                $this->propertyList[$node->name->name] = $assignment->expr->getAttribute(self::AST_KEY);
                break;
            case $node instanceof MethodCall
                && $node->var instanceof PropertyFetch:
                if (!array_key_exists($node->var->name->name, $this->propertyList)) {
                    $methodCallAdapter = new MethodCallAdapter($node);
                    throw new ParserException(
                        'Unknown property ' . $node->var->name->name
                        . ' in ' . $methodCallAdapter->getEnclosingClassFullyQualifiedName()
                        . '::' . $methodCallAdapter->getEnclosingMethodCanonicalName()
                    );
                }
                $node->var->setAttribute(
                    self::AST_KEY,
                    $this->propertyList[$node->var->name->name]
                );
                break;
        }
    }

    public function leaveNode(Node $node): void
    {
        if ($node instanceof Class_) {
            $this->resetPropertyList();
        }
    }

    private function resetPropertyList(): void
    {
        $this->propertyList = [];
    }
}
