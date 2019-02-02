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
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\VariableNodeDecorator;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeNodeCollector;
use PhpParser\Node;
use PhpParser\Node\Expr\Variable;

final class VariableNodeStrategy extends AbstractStrategy
{
    use NodeDecoratorAccessorTrait;

    /**
     * @var TypeNodeCollector
     */
    private $variableCollector;

    public function __construct(TypeNodeCollector $variableCollector)
    {
        $this->variableCollector = $variableCollector;
    }

    /**
     * @param Node|Variable $variableNode
     */
    public function enterNode(Node $variableNode): void
    {
        $this->validateNode($variableNode);

        /** @var VariableNodeDecorator $variableNodeDecorator */
        $variableNodeDecorator = $this->getNodeDecorator($variableNode);

        if ($variableNodeDecorator->isParameterDeclaration()) {
            $this->variableCollector->collectNodeFor($variableNodeDecorator);

            return;
        }

        if ($variableNodeDecorator->isAssignee()) {
            $this->variableCollector->collectNodeFor($variableNodeDecorator);

            return;
        }

        $variableNodeDecorator->addSiblingNodes(
            ...$this->variableCollector->getNodesFor($variableNodeDecorator)
        );
    }

    public static function getNodeTypeHandled(): string
    {
        return Variable::class;
    }
}
