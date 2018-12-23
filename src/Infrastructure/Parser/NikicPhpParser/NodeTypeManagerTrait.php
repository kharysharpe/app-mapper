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

use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\TypeNotFoundInNodeException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Type;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeCollection;
use PhpParser\Node;

trait NodeTypeManagerTrait
{
    public function addTypeCollectionToNode(Node $node, TypeCollection $newTypeCollection): void
    {
        if (!$node->hasAttribute(TypeCollection::getName())) {
            $node->setAttribute(TypeCollection::getName(), $newTypeCollection);

            return;
        }

        /** @var TypeCollection $typeCollection */
        $typeCollection = $node->getAttribute(TypeCollection::getName());
        $typeCollection->addTypeCollection($newTypeCollection);
    }

    public function addTypeToNode(Node $node, Type ...$typeList): void
    {
        if (!$node->hasAttribute(TypeCollection::getName())) {
            $typeCollection = new TypeCollection($node);
            $node->setAttribute(TypeCollection::getName(), $typeCollection);
        } else {
            $typeCollection = $node->getAttribute(TypeCollection::getName());
        }

        foreach ($typeList as $type) {
            $typeCollection->addType($type);
        }
    }

    public static function getTypeCollectionFromNode(?Node $node): TypeCollection
    {
        if (!$node) {
            return new TypeCollection();
        }
        if (!$node->hasAttribute(TypeCollection::getName())) {
            throw new TypeNotFoundInNodeException($node);
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
}
