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
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\AbstractNodeDecorator;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\ForeachNodeDecorator;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\PropertyFetchNodeDecorator;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\VariableNodeDecorator;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeNodeCollector;
use PhpParser\Node;
use PhpParser\Node\Stmt\Foreach_;

final class ForeachNodeStrategy extends AbstractStrategy
{
    use NodeDecoratorAccessorTrait;

    /**
     * @var TypeNodeCollector
     */
    private $propertyCollector;

    /**
     * @var TypeNodeCollector
     */
    private $variableCollector;

    public function __construct(TypeNodeCollector $propertyCollector, TypeNodeCollector $variableCollector)
    {
        $this->propertyCollector = $propertyCollector;
        $this->variableCollector = $variableCollector;
    }

    /**
     * @param Node|Foreach_ $foreachNode
     */
    public function leaveNode(Node $foreachNode): void
    {
        $this->validateNode($foreachNode);

        /** @var ForeachNodeDecorator $foreachNodeDecorator */
        $foreachNodeDecorator = $this->getNodeDecorator($foreachNode);

        $this->collectKeyVar($foreachNodeDecorator->getKeyVar());
        $this->collect($foreachNodeDecorator->getValueVar());
    }

    public static function getNodeTypeHandled(): string
    {
        return Foreach_::class;
    }

    private function collectKeyVar(?AbstractNodeDecorator $keyVarDecorator): void
    {
        if ($keyVarDecorator === null) {
            return;
        }

        $this->collect($keyVarDecorator);
    }

    private function collect(AbstractNodeDecorator $exprDecorator): void
    {
        if ($exprDecorator instanceof VariableNodeDecorator) {
            // Assignment to variable
            $this->variableCollector->reassign($exprDecorator);
        } elseif ($exprDecorator instanceof PropertyFetchNodeDecorator) {
            // Assignment to property
            $this->propertyCollector->collectNodeFor($exprDecorator);
        }
    }
}
