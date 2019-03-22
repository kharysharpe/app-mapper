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
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeNodeCollector;
use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;

final class PropertyFetchNodeStrategy extends AbstractStrategy
{
    use NodeDecoratorAccessorTrait;

    private $propertyFetchCollector;

    public function __construct(TypeNodeCollector $propertyFetchCollector)
    {
        $this->propertyFetchCollector = $propertyFetchCollector;
    }

    /**
     * @param Node|PropertyFetch $propertyFetchNode
     */
    public function enterNode(Node $propertyFetchNode): void
    {
        $this->validateNode($propertyFetchNode);

        /** @var PropertyFetchNodeDecorator $propertyFetchNodeDecorator */
        $propertyFetchNodeDecorator = $this->getNodeDecorator($propertyFetchNode);

        if ($propertyFetchNodeDecorator->isAssignee()) {
            $this->propertyFetchCollector->collectNode($propertyFetchNodeDecorator);

            return;
        }

        $propertyFetchNodeDecorator->addSiblingNodes(
            ...$this->propertyFetchCollector->getNodesFor($propertyFetchNodeDecorator)
        );
    }

    public static function getNodeTypeHandled(): string
    {
        return PropertyFetch::class;
    }
}
