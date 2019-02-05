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
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\PropertyNodeDecorator;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\StmtClassNodeDecorator;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeNodeCollector;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Trait_;

abstract class AbstractPropertyContainerNodeStrategy extends AbstractStrategy
{
    use NodeDecoratorAccessorTrait;

    /**
     * @var TypeNodeCollector
     */
    private $propertyFetchCollector;

    public function __construct(TypeNodeCollector $propertyFetchCollector)
    {
        $this->propertyFetchCollector = $propertyFetchCollector;
    }

    /**
     * @param Class_|Trait_ $node
     */
    public function leaveNode(Node $node): void
    {
        $this->validateNode($node);

        $this->addCollectedPropertySiblingsToTheirDeclaration($node);
        $this->storePropertiesSiblingsInNode($node);
        $this->propertyFetchCollector->reset();
    }

    /**
     * After collecting app possible class properties, we inject them in their declaration
     *
     * @param Class_|Trait_ $node
     */
    private function addCollectedPropertySiblingsToTheirDeclaration(Node $node): void
    {
        foreach ($node->stmts as $stmt) {
            $stmtNodeDecorator = $this->getNodeDecorator($stmt);
            if ($stmtNodeDecorator instanceof PropertyNodeDecorator) {
                $stmtNodeDecorator->addSiblingNodes(
                    ...$this->excludeNode(
                    $this->propertyFetchCollector->getNodesFor($stmtNodeDecorator),
                    $stmtNodeDecorator
                )
                );
            }
        }
    }

    private function excludeNode(array $nodeList, AbstractNodeDecorator $nodeToRemove): array
    {
        return array_filter(
            $nodeList,
            function (AbstractNodeDecorator $nodeDecorator) use ($nodeToRemove) {
                return $nodeDecorator !== $nodeToRemove;
            }
        );
    }

    private function storePropertiesSiblingsInNode(Node $node): void
    {
        /** @var StmtClassNodeDecorator $classNodeDecorator */
        $classNodeDecorator = $this->getNodeDecorator($node);
        $classNodeDecorator->storePropertiesSiblings($this->propertyFetchCollector->clone());
    }
}
