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
            case $node instanceof ConstFetch:
                $name = (string) $node->name;
                switch (true) {
                    case $name === 'true' || $name === 'false':
                        $this->addTypeToNode($node, new Type('bool'));
                        break;
                    case $name === 'null':
                        $this->addTypeToNode($node, new Type('null'));
                        break;
                }
                break;
            case $node instanceof FuncCall:
                if ($this->isNative($node)) {
                    $this->addTypeToNode($node, new Type($this->getReturnType($node)));
                }
                break;
            case $node instanceof Coalesce:
                if (!self::hasTypeCollection($node->left) || !self::hasTypeCollection($node->right)) {
                    // TODO stop ignoring unresolved and resolve all detected
                    return;
                }
                $this->addTypeCollectionToNode($node, self::getTypeCollectionFromNode($node->left));
                $this->addTypeCollectionToNode($node, self::getTypeCollectionFromNode($node->right));
                break;
            case $node instanceof Ternary:
                if (!self::hasTypeCollection($node->if) || !self::hasTypeCollection($node->else)) {
                    // TODO stop ignoring unresolved and resolve all detected
                    return;
                }
                $this->addTypeCollectionToNode($node, self::getTypeCollectionFromNode($node->if));
                $this->addTypeCollectionToNode($node, self::getTypeCollectionFromNode($node->else));
                break;
            case $node instanceof Variable:
                $this->addCollectedVariableTypes($node);
                break;
            case $node instanceof Assign:
                if (!self::hasTypeCollection($node->expr)) {
                    // TODO stop ignoring unresolved and resolve all detected
                    return;
                }
                $this->addTypeCollectionToNode($node->var, self::getTypeCollectionFromNode($node->expr));
                $this->collectVariableTypes($node->var);
                break;
            case $node instanceof ClassMethod:
                $this->resetVariableTypeBuffer();
                break;
            case $node instanceof Class_:
                $this->addPropertiesTypeToTheirDeclaration($node);
                $this->resetPropertyTypeBuffer();
                break;
        }
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
