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

namespace Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator;

use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeDecoratorAccessorTrait;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeCollection;
use PhpParser\Node\Stmt\TraitUse;

/**
 * @property TraitUse $node
 */
final class TraitUseNodeDecorator extends AbstractNodeDecorator
{
    use NodeDecoratorAccessorTrait;

    public function __construct(TraitUse $node, AbstractNodeDecorator $parentNode)
    {
        parent::__construct($node, $parentNode);
    }

    public function resolveTypeCollection(): TypeCollection
    {
        $typeCollection = new TypeCollection();

        foreach ($this->getTraitNameNodeDecoratorList() as $traitNameNodeDecorator) {
            $typeCollection = $typeCollection->addTypeCollection($traitNameNodeDecorator->getTypeCollection());
        }

        return $typeCollection;
    }

    /**
     * @return TraitNodeDecorator[]
     */
    public function getTraitNodeDecoratorList(): array
    {
        $traitNodeDecoratorList = [];
        $typeCollection = $this->getTypeCollection();
        foreach ($typeCollection as $type) {
            $traitNodeDecoratorList[] = $type->getNodeDecorator();
        }

        return $traitNodeDecoratorList;
    }

    /**
     * @return NameNodeDecorator[]
     */
    private function getTraitNameNodeDecoratorList(): array
    {
        $traitNameNodeDecoratorList = [];
        foreach ($this->node->traits as $trait) {
            $traitNameNodeDecoratorList[] = $this->getNodeDecorator($trait);
        }

        return $traitNameNodeDecoratorList;
    }
}
