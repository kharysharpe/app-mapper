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
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;

final class AssignmentFromConditionalTypeInjectorVisitor extends AbstractTypeInjectorVisitor
{
    use NativeFunctionsTrait;
    use PropertyBufferTrait;
    use VariableBufferTrait;

    public function leaveNode(Node $node): void
    {
        switch (true) {
            case $node instanceof Expr:
                $this->leaveExprNode($node);
                break;
            case $node instanceof ClassMethod:
                $this->leaveClassMethodNode();
                break;
            case $node instanceof Class_:
                $this->leaveClassNode($node);
                break;
        }
    }

    private function leaveExprNode(Expr $exprNode): void
    {
        switch (true) {
            case $exprNode instanceof ConstFetch:
                $this->leaveConstFetchNode($exprNode);
                break;
            case $exprNode instanceof FuncCall:
                $this->leaveFuncCallNode($exprNode);
                break;
            case $exprNode instanceof Coalesce:
                $this->leaveCoalesceNode($exprNode);
                break;
            case $exprNode instanceof Ternary:
                $this->leaveTernaryNode($exprNode);
                break;
            case $exprNode instanceof Variable:
                $this->leaveVariableNode($exprNode);
                break;
            case $exprNode instanceof Assign:
                $this->leaveAssignNode($exprNode);
                break;
        }
    }

    private function leaveConstFetchNode(ConstFetch $constFetchNode): void
    {
        $name = (string) $constFetchNode->name;
        switch (true) {
            case $name === 'true' || $name === 'false':
                $this->addTypeToNode($constFetchNode, new Type('bool'));
                break;
            case $name === 'null':
                $this->addTypeToNode($constFetchNode, new Type('null'));
                break;
        }
    }

    private function leaveFuncCallNode(FuncCall $funcCallNode): void
    {
        if ($this->isNative($funcCallNode)) {
            $this->addTypeToNode($funcCallNode, new Type($this->getReturnType($funcCallNode)));
        }
        // TODO if it's not native, try to find it in the AstMap
    }

    private function leaveCoalesceNode(Coalesce $coalesceNode): void
    {
        if (!self::hasTypeCollection($coalesceNode->left) || !self::hasTypeCollection($coalesceNode->right)) {
            // TODO stop ignoring unresolved and resolve all detected
            return;
        }
        $this->addTypeCollectionToNode($coalesceNode, self::getTypeCollectionFromNode($coalesceNode->left));
        $this->addTypeCollectionToNode($coalesceNode, self::getTypeCollectionFromNode($coalesceNode->right));
    }

    private function leaveTernaryNode(Ternary $ternaryNode): void
    {
        if (!self::hasTypeCollection($ternaryNode->if) || !self::hasTypeCollection($ternaryNode->else)) {
            // TODO stop ignoring unresolved and resolve all detected
            return;
        }
        $this->addTypeCollectionToNode($ternaryNode, self::getTypeCollectionFromNode($ternaryNode->if));
        $this->addTypeCollectionToNode($ternaryNode, self::getTypeCollectionFromNode($ternaryNode->else));
    }

    private function leaveVariableNode(Variable $variableNode): void
    {
        $this->addCollectedVariableTypes($variableNode);
    }

    private function leaveAssignNode(Assign $assignNode): void
    {
        if (!self::hasTypeCollection($assignNode->expr)) {
            // TODO stop ignoring unresolved and resolve all detected
            return;
        }
        $this->addTypeCollectionToNode($assignNode->var, self::getTypeCollectionFromNode($assignNode->expr));
        $this->collectVariableTypes($assignNode->var);
    }

    private function leaveClassMethodNode(): void
    {
        $this->resetVariableTypeBuffer();
    }

    private function leaveClassNode(Class_ $classNode): void
    {
        $this->addPropertiesTypeToTheirDeclaration($classNode);
        $this->resetPropertyTypeBuffer();
    }

    private function collectVariableTypes(Expr $var): void
    {
        $typeCollection = self::getTypeCollectionFromNode($var);
        switch (true) {
            case $var instanceof Variable: // Assignment to variable
                $this->addVariableTypeToBuffer($this->getVariableName($var), $typeCollection);
                break;
            case $var instanceof PropertyFetch: // Assignment to property
                $this->addPropertyTypeToBuffer($this->getPropertyName($var), $typeCollection);
                break;
        }
    }

    private function addCollectedVariableTypes(Variable $variable): void
    {
        $variableName = $this->getVariableName($variable);

        if (!$this->hasVariableTypeInBuffer($variableName)) {
            return;
        }

        $this->addTypeCollectionToNode(
            $variable,
            $this->getVariableTypeFromBuffer($variableName)
        );
    }
}
