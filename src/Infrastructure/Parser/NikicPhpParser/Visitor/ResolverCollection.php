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

use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\AbstractCollection;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeTypeManagerTrait;
use Hgraca\PhpExtension\String\ClassHelper;

/**
 * FIXME make this collection immutable, and with a fluent interface
 *
 * @property callable[] $itemList
 */
final class ResolverCollection extends AbstractCollection
{
    use NodeTypeManagerTrait;

    public function __construct(callable ...$itemList)
    {
        foreach ($itemList as $resolver) {
            $resolverId = spl_object_hash((object) $resolver);
            $this->itemList[$resolverId] = $resolver;
        }
    }

    public static function getName(): string
    {
        return ClassHelper::extractCanonicalClassName(__CLASS__);
    }

    public function addResolver(callable $resolver): self
    {
        $itemList = $this->itemList;

        $resolverId = spl_object_hash((object) $resolver);
        $itemList[$resolverId] = $resolver;

        return new self(...array_values($itemList));
    }

    public function addResolverCollection(self $newResolverCollection): self
    {
        $itemList = $this->itemList;
        /** @var callable $resolver */
        foreach ($newResolverCollection as $resolver) {
            $resolverId = spl_object_hash((object) $resolver);
            $itemList[$resolverId] = $resolver;
        }

        return new self(...array_values($itemList));
    }

    public function resolve(): TypeCollection
    {
        $typeCollection = new TypeCollection();
        foreach ($this->itemList as $resolver) {
            $typeCollection = $typeCollection->addTypeCollection($resolver());
        }

        return $typeCollection;
    }
}
