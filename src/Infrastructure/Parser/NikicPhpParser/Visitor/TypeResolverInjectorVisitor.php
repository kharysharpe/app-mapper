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

use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeCollection;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Strategy\AssignNodeStrategy;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Strategy\ClassMethodNodeStrategy;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Strategy\ClassNodeStrategy;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Strategy\CoalesceNodeStrategy;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Strategy\ForeachNodeStrategy;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Strategy\MethodCallNodeStrategy;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Strategy\NodeStrategy;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Strategy\NodeVisitorStrategyCollection;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Strategy\NullableNodeStrategy;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Strategy\PropertyFetchNodeStrategy;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Strategy\PropertyNodeStrategy;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Strategy\StaticCallNodeStrategy;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Strategy\TernaryNodeStrategy;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Strategy\TraitNodeStrategy;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Strategy\UseUseNodeStrategy;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Strategy\VariableNodeStrategy;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

final class TypeResolverInjectorVisitor extends NodeVisitorAbstract
{
    private $strategyCollection;

    public function __construct(NodeCollection $astCollection)
    {
        $typeFactory = new TypeFactory($astCollection);
        $propertyCollector = new TypeResolverCollector();
        $variableCollector = new TypeResolverCollector();

        $this->strategyCollection = new NodeVisitorStrategyCollection(
            new NodeStrategy($typeFactory),
            new AssignNodeStrategy($propertyCollector, $variableCollector),
            new ClassMethodNodeStrategy($variableCollector),
            new ClassNodeStrategy($propertyCollector),
            new CoalesceNodeStrategy($typeFactory),
            new ForeachNodeStrategy($propertyCollector, $variableCollector),
            new MethodCallNodeStrategy(),
            new NullableNodeStrategy($typeFactory),
            new PropertyFetchNodeStrategy($propertyCollector),
            new PropertyNodeStrategy($typeFactory, $propertyCollector),
            new StaticCallNodeStrategy(),
            new TernaryNodeStrategy(),
            new UseUseNodeStrategy(),
            new VariableNodeStrategy($typeFactory, $variableCollector),
            new TraitNodeStrategy($propertyCollector)
        );
    }

    public function enterNode(Node $node): void
    {
        $this->strategyCollection->getStrategyForNode($node)->enterNode($node);
    }

    public function leaveNode(Node $node): void
    {
        $this->strategyCollection->getStrategyForNode($node)->leaveNode($node);
    }
}
