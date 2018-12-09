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
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeVisitorAbstract;
use function array_key_exists;

class PropertyTypeInjectorVisitor extends NodeVisitorAbstract implements AstConnectorVisitorInterface
{
    /**
     * @var NodeCollection
     */
    private $ast;

    private $propertyList = [];

    public function __construct(NodeCollection $ast)
    {
        $this->ast = $ast;
    }

    public function enterNode(Node $node): void
    {
        switch (true) {
            case $node instanceof Property:
                $propertyName = $node->props[0]->name->name;
                $type = $node->getAttribute(self::KEY_AST);
                $this->addProperty($propertyName, $type);
                break;
            case $node instanceof PropertyFetch
                && $node->getAttribute(ParentConnectorVisitor::PARENT_NODE) instanceof Assign:
                // isPropertyAssignment
                /** @var Assign $assignment */
                $assignment = $node->getAttribute(ParentConnectorVisitor::PARENT_NODE);
                if ($assignment->var instanceof ArrayDimFetch) {
                    return; // TODO add the type to the variables assigned with `list`
                }
                $node->setAttribute(
                    self::KEY_AST,
                    $this->getProperty($node->name->name)
                );
                break;
            case $node instanceof MethodCall
                && $node->var instanceof PropertyFetch:
                // Method call on a property
                if (!array_key_exists($node->var->name->name, $this->propertyList)) {
                    return;
//                    $methodCallAdapter = new MethodCallAdapter($node);
//                    throw new ParserException(
//                        'Unknown property ' . $node->var->name->name
//                        . ' in ' . $methodCallAdapter->getEnclosingClassFullyQualifiedName()
//                        . '::' . $methodCallAdapter->getEnclosingMethodCanonicalName()
//                    );
                }
                $node->var->setAttribute(
                    self::KEY_AST,
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

    /**
     * @param string|Node $type
     */
    private function addProperty(string $name, $type): void
    {
        $this->propertyList[$name] = $type;
    }

    /**
     * @return string|Node
     */
    private function getProperty(string $name)
    {
        return $this->propertyList[$name] ?? '';
    }
}
