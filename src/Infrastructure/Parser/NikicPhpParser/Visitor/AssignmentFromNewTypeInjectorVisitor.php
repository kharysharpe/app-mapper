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
use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;

final class AssignmentFromNewTypeInjectorVisitor extends AbstractTypeInjectorVisitor
{
    use PropertyBufferTrait;
    use VariableBufferTrait;

    public function enterNode(Node $node): void
    {
        parent::enterNode($node);
        switch (true) {
            case $node instanceof Assign:
                $assignment = $node;

                if (!$assignment->expr instanceof New_) {
                    return;
                }
                $var = $assignment->var;
                $new = $assignment->expr;

                switch (true) {
                    case $var instanceof Variable: // Assignment of a new instance to variable
                        $typeCollection = self::getTypeCollectionFromNode($new);
                        $this->addTypeCollectionToNode($var, $typeCollection);
                        $this->addVariableTypeToBuffer($this->getVariableName($var), $typeCollection);
                        break;
                    case $var instanceof PropertyFetch: // Assignment of a new instance to property
                        $typeCollection = self::getTypeCollectionFromNode($new);
                        $this->addTypeCollectionToNode($var, $typeCollection);
                        $this->addPropertyTypeToBuffer($this->getPropertyName($var), $typeCollection);
                        break;
                }
                break;
            case $node instanceof Variable:
                // After collecting the variable types, inject it in the following variable nodes
                if ($this->hasVariableTypeInBuffer($this->getVariableName($node))) {
                    $this->addTypeCollectionToNode(
                        $node,
                        $this->getVariableTypeFromBuffer($this->getVariableName($node))
                    );
                }
                break;
        }
    }

    public function leaveNode(Node $node): void
    {
        if ($node instanceof Class_) {
            $this->addPropertiesTypeToTheirDeclaration($node);
            StaticLoggerFacade::notice(
                "We are only adding properties types in the class itself.\n"
                . "We should fix this by adding them also to the super classes and traits.\n",
                [__METHOD__]
            );
            $this->resetPropertyTypeBuffer();
        }
        if ($node instanceof ClassMethod) {
            $this->resetVariableTypeBuffer();
        }
    }
}
