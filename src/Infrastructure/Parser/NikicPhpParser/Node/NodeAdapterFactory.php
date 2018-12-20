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

namespace Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Node;

use Hgraca\ContextMapper\Core\Port\Parser\Node\AdapterNodeCollection;
use Hgraca\ContextMapper\Core\Port\Parser\Node\AdapterNodeInterface;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Type;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeCollection;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;

final class NodeAdapterFactory
{
    /**
     * @param null|string|Node $parserNode
     */
    public static function constructFromNode($parserNode): AdapterNodeInterface
    {
        if ($parserNode instanceof ClassMethod) { // this needs to be above the `Expr`
            return new MethodAdapter($parserNode);
        }

        if ($parserNode instanceof MethodCall) {
            return new MethodCallAdapter($parserNode);
        }

        if ($parserNode instanceof Class_) {
            return ClassAdapter::constructFromClassNode($parserNode);
        }

//        $parserNodeTypeCollection = AbstractTypeInjectorVisitor::getTypeCollectionFromNode($parserNode);
//        if ($parserNode instanceof Arg) {
//            return MethodArgumentAdapter::constructFromTypeCollection($parserNode);
//        }
//
//        if ($parserNodeTypeCollection->hasValidFqcn()) {
//            return FullyQualifiedTypeNode::constructFromTypeCollection($parserNodeTypeCollection);
//        }

//        throw new ParserException('Can\'t construct adapter node from ' . get_class($parserNode));
        return new UnknownTypeNode($parserNode);
    }

    public static function constructFromTypeCollection(TypeCollection $typeCollection): AdapterNodeCollection
    {
        $adapterNodeList = [];

        /** @var Type $type */
        foreach ($typeCollection as $type) {
            $adapterNodeList[] = $type->hasAst()
                ? self::constructFromNode($type->getAst())
                : new FullyQualifiedTypeNode((string) $type);
        }

        return new AdapterNodeCollection(...$adapterNodeList);
    }
}
