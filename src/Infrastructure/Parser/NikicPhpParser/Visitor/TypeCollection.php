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
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\EmptyCollectionException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\NonUniqueTypeCollectionException;
use Hgraca\PhpExtension\String\ClassHelper;
use function array_key_exists;
use function array_values;

/**
 * @property Type[] $itemList
 */
final class TypeCollection extends AbstractCollection
{
    public function __construct(Type ...$itemList)
    {
        foreach ($itemList as $type) {
            $this->itemList[$type->getFqn()] = $type;
        }
    }

    public static function getName(): string
    {
        return ClassHelper::extractCanonicalClassName(__CLASS__);
    }

    public function addType(Type $item): self
    {
        $itemList = $this->itemList;

        if (isset($itemList[Type::UNKNOWN])) {
            unset($itemList[Type::UNKNOWN]);
        }

        $itemList[$item->getFqn()] = $item;

        return new self(...array_values($itemList));
    }

    public function addTypeCollection(self $newTypeCollection): self
    {
        $itemList = $this->itemList;
        /** @var Type $type */
        foreach ($newTypeCollection as $type) {
            $itemList[$type->getFqn()] = $type;
        }

        return new self(...array_values($itemList));
    }

    public function getUniqueType(): Type
    {
        if ($this->count() > 1) {
            throw new NonUniqueTypeCollectionException($this);
        }

        if ($this->count() === 0) {
            throw new EmptyCollectionException();
        }

        return reset($this->itemList);
    }

    public function removeTypeEqualTo(Type $typeToRemove): self
    {
        $itemList = $this->itemList;
        if ($this->hasType($typeToRemove->getFqn())) {
            unset($itemList[$typeToRemove->getFqn()]);
        }

        return new self(...array_values($itemList));
    }

    private function hasType(string $typeFqn): bool
    {
        return array_key_exists($typeFqn, $this->itemList);
    }
}
