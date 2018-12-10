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

use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Exception\TypeNotFoundInNodeException;
use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Class_;

final class AssignmentFromParameterTypeInjectorVisitor extends AbstractTypeInjectorVisitor
{
    use PropertyBufferTrait;

    public function enterNode(Node $node): void
    {
        parent::enterNode($node);
        if (!$node instanceof Assign) {
            return;
        }
        $assignment = $node;

        if (!$assignment->expr instanceof Variable) {
            return;
        }
        $var = $assignment->var;
        $exprVar = $assignment->expr;

        try {
            switch (true) {
                case $var instanceof Variable: // Assignment of variable to variable
                    $this->addTypeToNode($var, self::getTypeFromNode($exprVar));
                    break;
                case $var instanceof PropertyFetch: // Assignment of variable to property
                    $type = self::getTypeFromNode($exprVar);
                    $this->addTypeToNode($var, $type);
                    $this->addPropertyTypeToBuffer($this->getPropertyName($var), $type);
                    break;
            }
        } catch (TypeNotFoundInNodeException $e) {
            // we ignore for now, it was happening because we dont infer variable
            // type from MethodCall nor StaticCall
        }
    }

    public function leaveNode(Node $node): void
    {
        if (!$node instanceof Class_) {
            return;
        }
        $this->addPropertiesTypeToTheirDeclaration($node);
        // TODO should follow family and traits up and set the types to those properties
    }
}
