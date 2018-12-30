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

/**
 * @property Type[] $itemList
 */
final class TypeCollection extends AbstractCollection
{
    public function __construct(Type ...$itemList)
    {
        parent::__construct($itemList);
    }

    public static function getName(): string
    {
        return ClassHelper::extractCanonicalClassName(__CLASS__);
    }

    public function addType(Type $item): void
    {
        $this->itemList[$item->getFcqn()] = $item;
    }

    public function addTypeCollection(self $newTypeCollection): void
    {
        foreach ($newTypeCollection as $type) {
            $this->addType($type);
        }
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
}
