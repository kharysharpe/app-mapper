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

namespace Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Strategy;

use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeDecoratorAccessorTrait;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\PropertyFetchNodeDecorator;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\VariableNodeDecorator;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeNodeCollector;
use PhpParser\Node;
use PhpParser\Node\Expr\Assign;

final class AssignNodeStrategy extends AbstractStrategy
{
    use NodeDecoratorAccessorTrait;

    private $propertyFetchCollector;

    private $variableCollector;

    public function __construct(TypeNodeCollector $propertyFetchCollector, TypeNodeCollector $variableCollector)
    {
        $this->propertyFetchCollector = $propertyFetchCollector;
        $this->variableCollector = $variableCollector;
    }

    /**
     * @param Node|Assign $assignNode
     */
    public function leaveNode(Node $assignNode): void
    {
        $this->validateNode($assignNode);

        $variableDecorator = $this->getNodeDecorator($assignNode->var);
        $expressionDecorator = $this->getNodeDecorator($assignNode->expr);

        $variableDecorator->addSiblingNodes($expressionDecorator);

        if ($variableDecorator instanceof VariableNodeDecorator) {
            // Assignment to variableDecorator
            $this->variableCollector->reassign($variableDecorator);
        } elseif ($variableDecorator instanceof PropertyFetchNodeDecorator) {
            // Assignment to property
            $this->propertyFetchCollector->reassign($variableDecorator);
        }
    }

    public static function getNodeTypeHandled(): string
    {
        return Assign::class;
    }
}
