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

use Hgraca\AppMapper\Core\Port\Logger\StaticLoggerFacade;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\MethodNotFoundInClassException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\TypeNotFoundInNodeException;
use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Class_;

final class AssignmentFromMethodCallTypeInjectorVisitor extends AbstractTypeInjectorVisitor
{
    public function enterNode(Node $node): void
    {
        switch (true) {
            case $node instanceof Assign:
                $assignment = $node;
                if (!$assignment->expr instanceof MethodCall) {
                    return;
                }
                $var = $assignment->var;
                $methodCall = $assignment->expr;

                $this->addTypeToMethodCall($methodCall);

                try {
                    // Assignment of a StaticCall to variable or property
                    $typeCollection = self::getTypeCollectionFromNode($methodCall);
                    $this->addTypeCollectionToNode($var, $typeCollection);
                    $this->collectVariableTypes($var);
                } catch (TypeNotFoundInNodeException $e) {
                    StaticLoggerFacade::warning(
                        "Silently ignoring a TypeNotFoundInNodeException in this filter.\n"
                        . 'This is failing, at least, for nested method calls like'
                        . '`$invoice->transactions->first()->getServicePro();`.' . "\n"
                        . "This should be fixed in the type addition visitors.\n"
                        . $e->getMessage(),
                        [__METHOD__]
                    );
                }
                break;
            case $node instanceof Variable:
                // After collecting the variable types, inject it in the following variable nodes
                $this->addCollectedVariableTypes($node);
                break;
        }
    }

    public function leaveNode(Node $node): void
    {
        if ($node instanceof Class_) {
            $this->addCollectedPropertiesTypeToTheirDeclaration($node);
        }
        parent::leaveNode($node);
    }

    private function addTypeToMethodCall(MethodCall $methodCall): void
    {
        try {
            $varCollectionType = self::getTypeCollectionFromNode($methodCall->var);

            /** @var Type $methodCallVarType */
            foreach ($varCollectionType as $methodCallVarType) {
                if (!$methodCallVarType->hasAst()) {
                    $this->addTypeToNode($methodCall, Type::constructUnknownFromNode($methodCall));
                    continue;
                }

                $returnTypeNode = $methodCallVarType->getAstMethod((string) $methodCall->name)->returnType;
                if ($returnTypeNode === null) {
                    $this->addTypeToNode($methodCall, Type::constructVoid());
                } else {
                    $classMethodReturnTypeCollection = self::getTypeCollectionFromNode($returnTypeNode);
                    $this->addTypeCollectionToNode($methodCall, $classMethodReturnTypeCollection);
                }
            }
        } catch (TypeNotFoundInNodeException $e) {
            StaticLoggerFacade::warning(
                "Silently ignoring a TypeNotFoundInNodeException.\n"
                . 'This is failing, at least, for nested method calls like'
                . '`$invoice->transactions->first()->getServicePro();`.' . "\n"
                . "This should be fixed in the type addition visitors, otherwise we might be missing events.\n"
                . $e->getMessage(),
                [__METHOD__]
            );
        } catch (MethodNotFoundInClassException $e) {
            StaticLoggerFacade::warning(
                "Silently ignoring a MethodNotFoundInClassException.\n"
                . "This should be fixed, otherwise we might be missing events.\n"
                . $e->getMessage(),
                [__METHOD__]
            );
        }
    }
}
