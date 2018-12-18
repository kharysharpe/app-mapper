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

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;

final class AssignmentFromMethodCallTypeInjectorVisitor extends AbstractTypeInjectorVisitor
{
    use PropertyBufferTrait;
    use VariableBufferTrait;

    public function enterNode(Node $node): void
    {
        parent::enterNode($node);
        switch (true) {
            case $node instanceof Assign:
                $assignment = $node;
                if (!$assignment->expr instanceof MethodCall) {
                    return;
                }
                $var = $assignment->var;
                $methodCall = $assignment->expr;

                $this->addTypeToMethodCall($methodCall);

                // Assignment of a StaticCall to variable or property
                $type = self::getTypeFromNode($methodCall);
                $this->addTypeToNode($var, $type);

                switch (true) {
                    case $var instanceof Variable: // Assignment of a new instance to variable
                        $this->addVariableTypeToBuffer($this->getVariableName($var), $type);
                        break;
                    case $var instanceof PropertyFetch: // Assignment of a new instance to property
                        $this->addPropertyTypeToBuffer($this->getPropertyName($var), $type);
                        break;
                }
                break;
            case $node instanceof Variable:
                // After collecting the variable types, inject it in the following variable nodes
                if ($this->hasVariableTypeInBuffer($this->getVariableName($node))) {
                    $this->addTypeToNode($node, $this->getVariableTypeFromBuffer($this->getVariableName($node)));
                }
                break;
        }
    }

    public function leaveNode(Node $node): void
    {
        if ($node instanceof Class_) {
            $this->addPropertiesTypeToTheirDeclaration($node);
            // TODO should follow family and traits up and set the types to those properties
            $this->resetPropertyTypeBuffer();
        }
        if ($node instanceof ClassMethod) {
            $this->resetVariableTypeBuffer();
        }
    }

    private function addTypeToMethodCall(MethodCall $methodCall): void
    {
        $varType = self::getTypeFromNode($methodCall->var);

        if ($varType->hasAst()) {
            $classMethodReturnType = self::getTypeFromNode(
                self::getTypeFromNode($methodCall->var)
                    ->getAstMethod((string) $methodCall->name)
                    ->returnType
            );

            $this->addTypeToNode($methodCall, $classMethodReturnType);
        }
    }
}