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
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeVisitorAbstract;
use function array_key_exists;

class PropertyDeclarationTypeInjectorVisitor extends NodeVisitorAbstract implements AstConnectorVisitorInterface
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
        // TODO deal with properties initialized in the `parent::__construct(...)` call
        // ie: NotifyServiceProAboutNewSavedSearchResultsLayoutGenerator::generate
        switch (true) {
            case $node instanceof PropertyFetch
                && $node->getAttribute(ParentConnectorVisitor::PARENT_NODE) instanceof Assign:
                // isPropertyAssignment
                /** @var Assign $assignment */
                $assignment = $node->getAttribute(ParentConnectorVisitor::PARENT_NODE);
                switch (true) {
                    case $assignment->expr instanceof Variable:
                    case $assignment->expr instanceof New_
                        && !$assignment->var instanceof ArrayDimFetch:
                        // TODO add the type to the variables assigned with `list`
                        $this->propertyList[$node->name->name] = $assignment->expr->getAttribute(self::KEY_AST);
                        break;
                }
                break;
        }
    }

    public function leaveNode(Node $node): void
    {
        if (!$node instanceof Class_) {
            return;
        }
        foreach ($node->stmts as $property) {
            if (
                $property instanceof Property
                && $this->hasProperty($property->props[0]->name->name)
            ) {
                $property->setAttribute(self::KEY_AST, $this->getProperty($property->props[0]->name->name));
            }
        }
        $this->resetPropertyList();
    }

    private function resetPropertyList(): void
    {
        $this->propertyList = [];
    }

    private function hasProperty(string $key): bool
    {
        return array_key_exists($key, $this->propertyList);
    }

    /**
     * @return string|Node
     */
    private function getProperty(string $key)
    {
        return $this->propertyList[$key];
    }
}
