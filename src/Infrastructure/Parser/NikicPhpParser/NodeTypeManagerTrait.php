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

namespace Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser;

use Hgraca\AppMapper\Core\Port\Logger\StaticLoggerFacade;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\UnresolvableNodeTypeException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\ResolverCollection;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Type;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeCollection;
use Hgraca\PhpExtension\Type\TypeHelper;
use PhpParser\Node;

trait NodeTypeManagerTrait
{
    public static function addTypeCollectionToNode(Node $node, TypeCollection $newTypeCollection): void
    {
        if (!$node->hasAttribute(TypeCollection::getName())) {
            $node->setAttribute(TypeCollection::getName(), $newTypeCollection);

            return;
        }

        /** @var TypeCollection $typeCollection */
        $typeCollection = $node->getAttribute(TypeCollection::getName())->addTypeCollection($newTypeCollection);

        $node->setAttribute(TypeCollection::getName(), $typeCollection);
    }

    public static function addTypeToNode(Node $node, Type ...$typeList): void
    {
        if (!$node->hasAttribute(TypeCollection::getName())) {
            $typeCollection = new TypeCollection();
            $node->setAttribute(TypeCollection::getName(), $typeCollection);
        } else {
            $typeCollection = $node->getAttribute(TypeCollection::getName());
        }

        foreach ($typeList as $type) {
            $typeCollection = $typeCollection->addType($type);
        }
    }

    public static function getTypeCollectionFromNode(?Node $node): TypeCollection
    {
        if (!$node) {
            return new TypeCollection(Type::constructVoid());
        }
        if (!self::hasTypeCollection($node)) {
            if (!self::hasTypeResolver($node)) {
                throw new UnresolvableNodeTypeException($node);
            }
            self::addTypeCollectionToNode($node, self::resolveType($node));
        }

        return $node->getAttribute(TypeCollection::getName());
    }

    public static function hasTypeCollection(?Node $node): bool
    {
        if (!$node) {
            return true; // because getTypeCollectionFromNode returns an empty collection in case of null
        }

        return $node->hasAttribute(TypeCollection::getName());
    }

    public static function addTypeResolver(Node $node, callable $typeResolver): void
    {
        $node->setAttribute(
            ResolverCollection::getName(),
            self::getNodeResolverCollection($node)->addResolver($typeResolver)
        );
    }

    public static function addTypeResolverCollection(Node $node, ResolverCollection $typeResolverCollection): void
    {
        $node->setAttribute(
            ResolverCollection::getName(),
            self::getNodeResolverCollection($node)->addResolverCollection($typeResolverCollection)
        );
    }

    public static function hasTypeResolver(Node $node): bool
    {
        return $node->hasAttribute(ResolverCollection::getName());
    }

    public static function resolveType(Node $node): TypeCollection
    {
        $relevantInfo = [];
        $loopNode = $node;
        while ($loopNode->hasAttribute('parentNode')) {
            $relevantInfo[] = get_class($loopNode) . ' => '
                . (property_exists($loopNode, 'name')
                    ? $loopNode->name
                    : (property_exists($loopNode, 'var') && property_exists($loopNode->var, 'name')
                        ? $loopNode->var->name
                        : 'no_name')
                );
            $loopNode = $loopNode->getAttribute('parentNode');
        }

        $message = 'Resolving type ' . TypeHelper::getType($node) . "\n"
            . json_encode($relevantInfo, JSON_PRETTY_PRINT);

        StaticLoggerFacade::debug($message);

        $resolverCollection = $node->getAttribute(ResolverCollection::getName());

        return $resolverCollection->resolve();
    }

    private static function getNodeResolverCollection(Node $node): ResolverCollection
    {
        if (!$node->hasAttribute(ResolverCollection::getName())) {
            $resolverCollection = new ResolverCollection();
            $node->setAttribute(ResolverCollection::getName(), $resolverCollection);
        }

        return $node->getAttribute(ResolverCollection::getName());
    }
}
