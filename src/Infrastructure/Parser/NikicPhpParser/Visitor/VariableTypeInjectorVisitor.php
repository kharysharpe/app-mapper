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
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Exception\UnknownVariableException;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitorAbstract;
use function array_key_exists;

class VariableTypeInjectorVisitor extends NodeVisitorAbstract implements AstConnectorVisitorInterface
{
    /**
     * @var AstMap
     */
    private $ast;

    private $assignedVariables = [];

    public function __construct(AstMap $ast)
    {
        $this->ast = $ast;
    }

    public function enterNode(Node $node): void
    {
        switch (true) {
            case $node instanceof ClassMethod:
                foreach ($node->params as $methodParameter) {
                    if ($methodParameter->type === null) {
                        return; // silently ignore
                    }
                    /** @var FullyQualified $name */
                    $name = $methodParameter->type->getAttribute('resolvedName');
                    if ($name === null) {
                        return; // silently ignore because it's not a class, so it's not an event
                    }

                    $this->setVariableTypeAst(
                        $this->resolveVariableNameFromMethodParameter($methodParameter),
                        $name->toCodeString()
                    );
                }
                break;
            case $node instanceof New_:
                $assignment = $node->getAttribute('parent');
                if (
                    !$assignment instanceof Assign
                    || $assignment->var instanceof ArrayDimFetch // TODO add the type to the variables assigned with `list`
                ) {
                    return;
                }

                $this->setVariableTypeAst(
                    $this->resolveVariableNameFromAssignment($assignment),
                    $node->class->getAttribute(self::AST_KEY)
                );
                break;
            case $node instanceof Variable:
                $variableName = $node->name;
                try {
                    $node->setAttribute(self::AST_KEY, $this->getVariableTypeAst($variableName));
                } catch (UnknownVariableException $e) {
                    // silently ignore unknown variables, because those were not instantiated nor injected
                    // TODO Also take into account variables assigned to the result of a method call
                }
                break;
        }
    }

    public function leaveNode(Node $node): void
    {
        if ($node instanceof ClassMethod) {
            $this->resetAssignedVariables();
        }
    }

    /**
     * @return string|Node
     */
    private function getVariableTypeAst(string $variableName)
    {
        if (!array_key_exists($variableName, $this->assignedVariables)) {
            throw new UnknownVariableException($variableName);
        }

        return $this->assignedVariables[$variableName];
    }

    private function resetAssignedVariables(): void
    {
        $this->assignedVariables = [];
    }

    private function setVariableTypeAst(string $variableName, $variableType): void
    {
        switch (true) {
            case $variableType instanceof Node:
                $this->assignedVariables[$variableName] = $variableType;
                break;
            case $this->ast->hasAstNode($variableType): // fqcn is known
                $this->assignedVariables[$variableName] = $this->ast->getAstNode($variableType);
                break;
            default: // fqcn is unknown
                $this->assignedVariables[$variableName] = $variableType;
        }
    }

    private function resolveVariableNameFromAssignment(Assign $assignment): string
    {
        $variableName = $assignment->var->name;
        if ($variableName instanceof Identifier) {
            $variableName = $variableName->name;
        }

        return $variableName;
    }

    private function resolveVariableNameFromMethodParameter(Param $methodParameter): string
    {
        return $methodParameter->var->name;
    }
}
