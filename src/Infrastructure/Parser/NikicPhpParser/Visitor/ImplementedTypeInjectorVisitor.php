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

use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\NodeCollection;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeVisitorAbstract;

class ImplementedTypeInjectorVisitor extends NodeVisitorAbstract implements AstConnectorVisitorInterface
{
    use TypeInjectorVisitorTrait;

    /**
     * @var NodeCollection
     */
    private $ast;

    public function __construct(NodeCollection $ast)
    {
        /* @noinspection UnusedConstructorDependenciesInspection Used in trait */
        $this->ast = $ast;
    }

    public function enterNode(Node $node): void
    {
        if (!$node instanceof Class_ || empty($node->implements)) {
            return;
        }

        foreach ($node->implements as $interface) {
            $this->addType($interface);
        }
    }
}
