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

use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Strategy\AssignNodeStrategy;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Strategy\ClassMethodNodeStrategy;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Strategy\ClassNodeStrategy;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Strategy\ForeachNodeStrategy;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Strategy\PropertyFetchNodeStrategy;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Strategy\PropertyNodeStrategy;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Strategy\TraitNodeStrategy;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Strategy\VariableNodeStrategy;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

final class TypeResolverInjectorVisitor extends NodeVisitorAbstract
{
    private $strategyCollection;

    public function __construct()
    {
        $propertyFetchCollector = new TypeNodeCollector();
        $variableCollector = new TypeNodeCollector();

        $this->strategyCollection = new NodeVisitorStrategyCollection(
            new AssignNodeStrategy($propertyFetchCollector, $variableCollector),
            new ClassMethodNodeStrategy($variableCollector, $propertyFetchCollector),
            new ClassNodeStrategy($propertyFetchCollector),
            new ForeachNodeStrategy($propertyFetchCollector, $variableCollector),
            new PropertyFetchNodeStrategy($propertyFetchCollector),
            new PropertyNodeStrategy($propertyFetchCollector),
            new TraitNodeStrategy($propertyFetchCollector),
            new VariableNodeStrategy($variableCollector)
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
