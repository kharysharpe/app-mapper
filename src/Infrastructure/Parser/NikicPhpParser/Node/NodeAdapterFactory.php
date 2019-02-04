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

namespace Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Node;

use Hgraca\AppMapper\Core\Port\Parser\Node\AdapterNodeCollection;
use Hgraca\AppMapper\Core\Port\Parser\Node\AdapterNodeInterface;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeDecoratorAccessorTrait;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\ClassMethodNodeDecorator;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\MethodCallNodeDecorator;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\StmtClassNodeDecorator;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Type;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeCollection;
use PhpParser\Node;

final class NodeAdapterFactory
{
    use NodeDecoratorAccessorTrait;

    /**
     * @param string|Node|null $node
     */
    public function constructFromNode($node): AdapterNodeInterface
    {
        $nodeDecorator = $node instanceof Node
            ? $this->getNodeDecorator($node)
            : $node;

        if ($nodeDecorator instanceof ClassMethodNodeDecorator) { // this needs to be above the `Expr`
            return new MethodAdapter($nodeDecorator);
        }

        if ($nodeDecorator instanceof MethodCallNodeDecorator) {
            return new MethodCallAdapter($nodeDecorator);
        }

        if ($nodeDecorator instanceof StmtClassNodeDecorator) {
            return ClassAdapter::constructFromClassNode($nodeDecorator);
        }

        return new UnknownTypeNode($nodeDecorator);
    }

    public function constructFromTypeCollection(TypeCollection $typeCollection): AdapterNodeCollection
    {
        $adapterNodeList = [];

        /** @var Type $type */
        foreach ($typeCollection as $type) {
            $adapterNodeList[] = $type->hasNode()
                ? $this->constructFromNode($type->getNodeDecorator())
                : new FullyQualifiedTypeNode((string) $type);
        }

        return new AdapterNodeCollection(...$adapterNodeList);
    }
}
